<?php
/*
* Copyright (c) 2024-present
* Author: tao996<lvshutao@outlook.com>
* 
* For the full copyright and license information, please view the LICENSE.txt
* file that was distributed with this source code.
*/

namespace App\Modules\tao\Common\Actions;

use App\Modules\tao\A0\open\Models\OpenUserOpenid;
use App\Modules\tao\A0\open\Models\OpenUserUnionid;
use App\Modules\tao\Helper\MyMvcHelper;
use App\Modules\tao\Models\SystemUser;
use App\Modules\tao\Services\SmsCodeService;
use App\Modules\tao\Services\UserService;
use Phax\Db\Transaction;
use Phax\Support\Exception\BusinessException;
use Phax\Support\Exception\LogException;
use Phax\Support\Logger;

/**
 * 账号迁移
 */
class AccountMigrateAction
{
    private SmsCodeService $smsCodeService;
    public int $max_count = 2;

    public function __construct(
        public MyMvcHelper $helper,
        public int         $user_id,
        public string      $appid,
    )
    {
        $this->smsCodeService = $this->helper->smsCodeService();
    }

    protected function getAccountInfo(string $account): array
    {
        if (empty($account)) {
            throw new BusinessException('请填写账号');
        }
        if ($this->helper->validate()->isPhone($account)) {
            $type = 'phone';
        } elseif ($this->helper->validate()->isEmail($account)) {
            $type = 'email';
        } else {
            throw new BusinessException('不支持的账号类型');
        }
        $condition = ['user_id' => $this->user_id, 'kind' => 'connect'];
        return [$type, $condition];
    }

    /**
     * 发送验证码
     * @throws \Exception
     */
    public function sendCode(string $account): bool
    {
        list($type, $condition) = $this->getAccountInfo($account);

        // 检查是否与账号一致
        $user = UserService::mustGetUser(['id' => $this->user_id]);
        if ($user->getAccountByType($type) === $account) {
            throw new BusinessException('重复绑定');
        }

        $history = $this->smsCodeService->getLast($condition);
        if ($history && $history->isActive()) {
            return true;
        }
        $count = $this->smsCodeService->todayCount($condition);
        if ($count >= $this->max_count) {
            throw new BusinessException('今日发送次数过多');
        }
        $condition['receiver'] = $account;
        $mSer = $this->smsCodeService->getMessageHelper();
        $code = $this->smsCodeService->insertOne($condition);

        if ('email' == $type) {
            $email = $mSer->email();
            $rst = $email->useSingleSendMailRequest()
                ->setSubject('账号关联')
                ->setAddress($account)
                ->setHtmlBody('您好，当前账号关联验证码为：' . $code->code)
                ->send();
            return $this->smsCodeService->updateSendStatus($email, $code, $rst);
        } else {
            $sms = $mSer->sms();;
            $rst = $sms->addTemplateCode(SmsCodeService::$code['change_account'])
                ->addPhoneNumber($account)
                ->addTemplateParams(['code' => $code->code])
                ->send();
            return $this->smsCodeService->updateSendStatus($sms, $code, $rst);
        }
    }

    public int $connect_result = 0;

    /**
     * 绑定账号
     * @param string $account 手机号或邮箱
     * @param string $verCode 用户提交的验证码
     * @param callable{SystemUser} $successMove 当手机号/邮箱已经存在时
     * @throws \Exception
     */
    public function connect(string $account, string $verCode, callable $successMove): void
    {
        if (empty($verCode)) {
            throw new BusinessException('请填写验证码');
        }
        list($type, $condition) = $this->getAccountInfo($account);
        $codeModel = $this->smsCodeService
            ->checkCode($account, $this->user_id, $condition['kind'], $verCode);
        Transaction::db(function () use ($account, $type, $codeModel, $successMove) {
            if ($user = UserService::findByAccount($account, $type)) {
                // 已经存在，则需要迁移；
                // 1. 将当前的应用 appid+user_id， open_user_openid/open_user_unionid 等账号迁移到新的账号 $user->id 上；
                // 2. 将当前应用的数据迁移到指定账户下
                if ($this->appid && $this->user_id) {
                    if (IS_DEBUG) {
                        Logger::debug('准备账号迁移', ['user_id' => $this->user_id, 'account' => $account, 'type' => $type], ['to_user_id' => $user->id]);
                    }
                    OpenUserOpenid::layer()->update(['user_id' => $user->id], [
                        'appid' => $this->appid,
                        'user_id' => $this->user_id,
                    ]);
                    OpenUserUnionid::layer()->update(['user_id' => $user->id], [
                        'appid' => $this->appid,
                        'user_id' => $this->user_id,
                    ]);
                }
                $successMove($user);
                $this->connect_result = 1;
            } else {
                // 如果手机号或邮箱不存在，则绑定到账号上
                $user = $this->helper->loginUserHelper()->user();
                $user->addAccount($account, $type);
                if (!$user->save()) {
                    throw new LogException('更新账号关联失败', [
                        'errors' => $user->getErrors(),
                        'user' => $user->toArray(),
                        'data' => ['account' => $account, 'type' => $type]
                    ]);
                }
            }
            $this->smsCodeService->done($codeModel);
        });
    }

    public function isMigrate(): bool
    {
        return $this->connect_result == 1;
    }

}