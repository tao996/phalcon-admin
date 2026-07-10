<?php

namespace App\Modules\tao\A0\open\Service;

use App\Modules\tao\A0\open\Models\OpenUserOpenid;
use App\Modules\tao\A0\open\Models\OpenUserUnionid;
use App\Modules\tao\Data\UserBindPlatform;
use App\Modules\tao\Models\SystemUser;
use App\Modules\tao\Services\UserService;
use Phax\Db\Transaction;
use Phax\Support\Exception\BusinessException;
use Phax\Support\Exception\LogException;
use Phax\Utils\MyAssert;


class OpenUserService
{
    /**
     * 查询 OpenUserOpenid 记录
     * @param string $appID
     * @param string $openid
     * @return OpenUserOpenid|null
     */
    public static function getOpenidRecord(string $appID, string $openid): OpenUserOpenid|null
    {
        if (empty($appID) || empty($openid)) {
            throw new BusinessException(' appID 或 openid 不能为空');
        }
        return OpenUserOpenid::findFirst([
            'conditions' => "appid='{$appID}' AND openid='{$openid}'",
        ]);
    }

    /**
     * 查询用户的的 openid，如果没有找到，则返回空字符串
     * @param string $appID
     * @param int $userId
     * @return string
     */
    public static function getOpenidByUserId(string $appID, int $userId): string
    {
        if (empty($appID) || empty($userId)) {
            throw new BusinessException('appID 或 userId 不能为空');
        }
        if ($row = OpenUserOpenid::findFirst([
            'conditions' => "appid='{$appID}' AND user_id='{$userId}'",
            'columns' => 'openid'
        ])?->toArray()) {
            return $row['openid'];
        }
        return '';
    }

    /**
     * 查询 OpenUserUnionid 记录
     * @param string $unionid
     * @return OpenUserUnionid|null
     */
    public static function getUnionIDRecord(string $unionid): OpenUserUnionid|null
    {
        if (empty($unionid)) {
            throw new BusinessException("unionID can't be empty");
        }
        return OpenUserUnionid::queryBuilder()
            ->string('unionid', $unionid)
            ->findFirstModel();
    }

    /**
     * 补全用户信息
     * @param array $userInfo
     * @return array{avatarUrl:string,nickname:string,gender:int}
     */
    public static function postUserInfo(array $userInfo): array
    {
        $rst = [
            'avatarUrl' => '',
            'nickname' => '',
            'gender' => 0,
        ];

        if (isset($userInfo['avatarUrl']) || isset($userInfo['headimgurl'])) {
            $rst['avatarUrl'] = $userInfo['avatarUrl'] ?? $userInfo['headimgurl'] ?? '';
        }
        if (isset($userInfo['nickName']) || isset($userInfo['nickname'])) {
            $rst['nickname'] = $userInfo['nickName'] ?? $userInfo['nickname'] ?? '';
        }
        if (isset($userInfo['gender']) || isset($userInfo['sex'])) {
            $rst['gender'] = intval($userInfo['gender'] ?? $userInfo['sex'] ?? 0);
        }

        return array_merge($userInfo, $rst);
    }

    /**
     * @param SystemUser $user
     * @param OpenUserOpenid $openidRecord
     * @param OpenUserUnionid|null $unionidRecord
     * @param array $postUserInfo
     * @param string $appid
     * @param array{openid:string,unionid:string} $data 用户在开放平台的数据
     * @return void
     */
    public static function bindUserInfo(
        SystemUser           $user,
        OpenUserOpenid       $openidRecord,
        OpenUserUnionid|null $unionidRecord,
        array                $postUserInfo,
        string               $appid,
        array                $data
    ): void
    {
        $user->nickname = $postUserInfo['nickname'];
        $user->avatar_url = $postUserInfo['avatarUrl'];

        $openidRecord->appid = $appid;
        $openidRecord->openid = $data['openid'];
        self::bindSubscribe($openidRecord, $data);

        $openidRecord->unionid = $data['unionid'];
        $openidRecord->avatar_url = $postUserInfo['avatarUrl'];
        $openidRecord->nickname = $postUserInfo['nickname'];

        if ($unionidRecord) {
            $unionidRecord->appid = $appid;
            $unionidRecord->unionid = $data['unionid'];
        }
    }

    /**
     * @param OpenUserOpenid $openidRecord
     * @param array $userInfo 用户的信息，通常由小程序提供
     * @param string $appid
     * @param array{openid:string,unionid:string,session_key:string,subscripte:string,subscribe_time:int} $data 包含用户的关键信息
     * @return void
     */
    public static function createOpenidRecord(
        OpenUserOpenid $openidRecord,
        array          $userInfo,
        string         $appid,
        array          $data
    ): void
    {
        $openidRecord->appid = $appid;
        $openidRecord->openid = $data['openid'];
        self::bindSubscribe($openidRecord, $data);
        $openidRecord->unionid = $data['unionid'];
        $openidRecord->avatar_url = $userInfo['avatarUrl'];
        $openidRecord->nickname = $userInfo['nickname'];
        $openidRecord->session_key = $data['session_key'] ?? '';
        if ($openidRecord->create() === false) {
            throw new LogException('保存 openid 关联记录失败', [
                'errors' => $openidRecord->getErrors(),
                'record' => $openidRecord->toArray(),
                'userInfo' => $userInfo, 'data' => $data,
            ]);
        }
    }


    /**
     * 创建用户
     * @throws \Exception
     */
    public static function createUser(
        SystemUser           $user,
        OpenUserOpenid       $openidRecord,
        OpenUserUnionid|null $unionidRecord = null,
    ): void
    {
        Transaction::db(function () use ($user, $openidRecord, $unionidRecord) {
            UserService::create($user);

            $openidRecord->user_id = $user->id;
            if (!$openidRecord->save()) {
                throw new LogException('openid 保存错误', [
                    'errors' => $openidRecord->getErrors(),
                    'openidRecord' => $openidRecord->toArray(),
                ]);
            }

            if ($unionidRecord) {
                $unionidRecord->user_id = $user->id;
                if (!$unionidRecord->save()) {
                    throw new LogException('unionid 保存错误', [
                        'errors' => $unionidRecord->getErrors(),
                        'unionidRecord' => $unionidRecord->toArray()
                    ]);
                }
            }
        });
    }


    public static function bindSubscribe(OpenUserOpenid $openidRecord, array $data): bool
    {
        $hasChange = false;
        if (isset($data['session_key'])) {
            $openidRecord->session_key = $data['session_key'];
            $hasChange = true;
        }
        if (isset($data['subscribe_time']) && $data['subscribe_time'] > 0) {
            $openidRecord->sub = 1;
            $openidRecord->sub_at = $data['subscribe_time'];
            $hasChange = true;
        }
        return $hasChange;
    }

    /**
     * 创建公众号用户
     * @param \EasyWeChat\OfficialAccount\Application $application 微信公众号
     * @param string $openid 用户 openid
     * @return array
     * @throws \Exception
     */
    public static function officialUser(\EasyWeChat\OfficialAccount\Application $application, string $openid): array
    {
        $appid = $application->getConfig()->get('app_id');
        // 查询当前用户是否已经记录
        $openidRecord = self::getOpenidRecord($appid, $openid);
        if (!empty($openidRecord)) {
            return self::responseData($openidRecord);
        }
        // https://developers.weixin.qq.com/doc/offiaccount/User_Management/Get_users_basic_information_UnionID.html#UinonId
        $data = $application->getClient()
            ->get('/cgi-bin/user/info', ['openid' => $openid])
            ->toArray();
        /*{
            "subscribe": 1, subscribe_time": 1382694957,
            "openid": "o6_bmjrPTlm6_2sgVt7hMZOPfL2M",
            "unionid": " o6_bmasdasdsad6_2sgVt7hMZOPfL",
        } */
        return self::save([
            'appid' => $appid,
            'platform' => UserBindPlatform::PlatformWechat,
            'kind' => 'official'
        ], $data, []);
    }

    /**
     * 取消订阅
     * @param array{ToUserName:string,FromUserName:string} $data 接收微信发送的信息
     * @return void
     */
    public static function unsubscribe(array $data): void
    {
        if ($record = self::getOpenidRecord($data['ToUserName'], $data['FromUserName'])) {
            $record->sub = 0;
            $record->sub_at = time();
            if ($record->save() === false) {
                throw new LogException('更新订阅时间错误', [
                    'errors' => $record->getErrors(),
                    'record' => $record->toArray(),
                    'data' => $data,
                ]);
            }
        }
    }


    /**
     * 获取基本信息
     * @param OpenUserOpenid $openidRecord
     * @return array{user_id:int,nickname:string,avatar_url:string,openid:string}
     */
    public static function responseData(OpenUserOpenid $openidRecord): array
    {
        return [
            'user_id' => $openidRecord->user_id,
            'nickname' => $openidRecord->nickname,
            'avatar_url' => $openidRecord->avatar_url,
            'openid' => $openidRecord->openid,
        ];
    }

    /**
     * 检查并保存用户信息
     * @param array $app 应用信息
     * @param array $data 包含用户关键信息 [openid, unionid, session_key, subscribe, subscribe_time]
     * @param array $userBaseInfo 用户的基本信息
     * @return array{user_id:int,nickname:string,avatar_url:string,openid:string,id?:int}
     * @throws \Exception
     */
    public static function save(array $app, array $data, array $userBaseInfo): array
    {
        MyAssert::mustHasSet($app, ['appid', 'platform', 'kind']);
        $userBind = OpenAppService::newUserBind($app);
        $userInfo = self::postUserInfo($userBaseInfo);

        // 检查 unionid 是否存在
        if (!empty($data['unionid'])) {
            $unionidRecord = self::getUnionIDRecord($data['unionid']);
            // 如果存在，则 userOpenid 必然存在
            if ($unionidRecord) {
                // 不再处理，可直接返回数据
                $responseData = OpenUserOpenid::queryBuilder()
                    ->string('appid', $app['appid'])
                    ->string('openid', $data['openid'])
                    ->columns(['id', 'user_id', 'nickname', 'avatar_url', 'openid'])
                    ->findFirstArray();
                if (empty($responseData)) {
                    $openidRecord = new OpenUserOpenid();
                    $openidRecord->platform = $app['platform'];
                    $openidRecord->user_id = $unionidRecord->user_id;
                    self::createOpenidRecord($openidRecord, $userInfo, $app['appid'], $data);
                    $responseData = [
                        'id' => $openidRecord->id,
                        'user_id' => $openidRecord->user_id,
                        'nickname' => $openidRecord->nickname,
                        'avatar_url' => $openidRecord->avatar_url,
                        'openid' => $openidRecord->openid,
                    ];
                }
                return $responseData;
            } else { // 用户没有注册过
                $responseData = OpenUserOpenid::queryBuilder()
                    ->string('appid', $app['appid'])
                    ->string('openid', $data['openid'])
                    ->columns(['id', 'user_id', 'nickname', 'avatar_url', 'openid'])
                    ->findFirstArray();
                if ($responseData) {// 统一应用后，现在有了 unionid
                    $unionidRecord = new OpenUserUnionid();
                    $unionidRecord->platform = $app['platform'];
                    $unionidRecord->user_id = $responseData['user_id'];
                    $unionidRecord->appid = $app['appid'];
                    if (!$unionidRecord->save()) {
                        throw new LogException('添加 unionid 记录错误',
                            [
                                'errors' => $unionidRecord->getErrors(),
                                'record' => $unionidRecord->toArray(),
                                'data' => $data,
                            ]);
                    }
                } else {
                    // Openid 和 Unionid 都没有，需要注册用户
                    $user = new SystemUser();
                    UserService::addBinds($user, $userBind);

                    $openidRecord = new OpenUserOpenid();
                    $openidRecord->platform = $app['platform'];

                    $unionidRecord = new OpenUserUnionid();
                    $unionidRecord->platform = $app['platform'];

                    self::bindUserInfo(
                        $user,
                        $openidRecord,
                        $unionidRecord,
                        $userInfo,
                        $app['appid'],
                        $data
                    );

                    self::createUser(
                        $user,
                        $openidRecord,
                        $unionidRecord
                    );

                    $responseData = [
                        'user_id' => $user->id,
                        'nickname' => $openidRecord->nickname,
                        'avatar_url' => $openidRecord->avatar_url,
                        'openid' => $data['openid']
                    ];
                }
            }
        } else {
            // 1. 检查 openid 记录是否存在
            /**
             * @var OpenUserOpenid $openidRecord
             */
            $openidRecord = OpenUserOpenid::queryBuilder()
                ->string('appid', $app['appid'])
                ->string('openid', $data['openid'])
                ->columns(['id', 'user_id', 'nickname', 'avatar_url', 'openid'])
                ->findFirstModel();
            // 如果不存在，则需要注册
            if (empty($openidRecord)) {
                $user = new SystemUser();
                UserService::addBinds($user, $userBind);

                $openidRecord = new OpenUserOpenid();
                $openidRecord->platform = $app['platform'];

                self::bindUserInfo(
                    $user,
                    $openidRecord,
                    null,
                    $userInfo,
                    $app['appid'],
                    $data
                );

                self::createUser(
                    $user,
                    $openidRecord,
                );


                $responseData = [
                    'user_id' => $user->id,
                    'nickname' => $openidRecord->nickname,
                    'avatar_url' => $openidRecord->avatar_url,
                    'openid' => $data['openid']
                ];
            } else {
                // 如果存在
                if (self::bindSubscribe($openidRecord, $data)) {
                    $openidRecord->save();
                }
                $responseData = $openidRecord->toArray([
                    'user_id',
                    'nickname',
                    'avatar_url',
                    'openid'
                ]);
            }
        }
        return $responseData;
    }
}