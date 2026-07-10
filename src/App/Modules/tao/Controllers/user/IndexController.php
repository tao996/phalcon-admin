<?php

namespace App\Modules\tao\Controllers\user;

use App\Modules\tao\BaseController;
use App\Modules\tao\Services\SmsCodeService;
use App\Modules\tao\Services\UserService;
use App\Modules\tao\TaoAppService;
use Phax\Db\Transaction;
use Phax\Foundation\AppService;
use Phax\Support\Exception\LogException;
use Phax\Support\Validate;
use Phax\Utils\MyData;

/**
 * 用户控制器
 */
class IndexController extends BaseController
{
    protected string $htmlTitle = '会员中心';
    protected array $allowModifyFields = ['status', 'nickname', 'head_img', 'signature'];
    protected array|string $userActions = '*';
    public array $smsConfig = [];

    /**
     * 基本资料
     * @throws \Exception
     */
    public function indexAction()
    {
        $user = $this->loginUser();

        if ($this->request->isPost()) {
            $data = $this->request->getPost();
            $newHeadImg = MyData::getString($data, 'head_img');
            Validate::hostValidate($newHeadImg);
            $user->head_img = $newHeadImg;
            $user->signature = MyData::getString($data, 'signature');
            if ($user->save()) {
                TaoAppService::loginUserHelper()->updateUserInfo([
                    'head_img' => $user->head_img,
                    'signature' => $user->signature,
                ]);
                TaoAppService::loginAuthHelper()->getAdapter()->saveUser(
                    TaoAppService::loginUserHelper()->user()
                );
                return $this->success('保存成功');
            } else {
                return $this->error($user->getErrors());
            }
        }
//        ddd($user, $this->vv->roleService()->);
        return [
            'roles' => $user->roles(),
        ];
    }

    /**
     * 查看账号的 PUID 码
     * @return string
     */
    public function puidAction(): string
    {
        $user = $this->loginUser();
        return join('.', [$user->puid, $user->id]);
    }

    /**
     * 修改手机号
     * @throws \Exception
     */
    public function changePhoneAction()
    {
        if ($this->request->isPost()) {
            $data = $this->request->getPost();
            MyData::mustHasSet($data, ['phone', 'vercode']);

            $code = SmsCodeService::checkChangeAccountCode(
                $this->loginUser()->id,
                $data['phone'],
                $data['vercode']
            );

            $user = $this->loginUser();
            Transaction::db(function () use ($user, $data, $code) {
                $user->phone = $data['phone'];
                $user->phone_at = time();
                $user->phone_valid = 1;
                if ($user->save() === false) {
                    throw new LogException('更新手机号码状态失败', ['user' => $user->toArray(), 'errors' => $user->getErrors()]);
                }
                SmsCodeService::done($code);
            });
            TaoAppService::loginUserHelper()->updateUserInfo($user->toArray());
            TaoAppService::loginAuthHelper()->getAdapter()->saveUser(
                TaoAppService::loginUserHelper()->user()
            );
            return $this->success('修改手机号成功');
        }

        return [
        ];
    }

    /**
     * 发送手机验证码
     * @throws \Exception
     */
    public function phoneCodeAction()
    {
        $this->mustPostMethod();
        $data = $this->request->getPost();
        MyData::mustHasSet($data, ['phone']);

        $user = $this->loginUser();
        UserService::mustAllowChangeAccount('phone', $data['phone'], $user);

        if (SmsCodeService::sendChangeAccountCode(
            $user->id,
            $data['phone'],
            $this->smsConfig,
        )) {
            return $this->success('验证码已发送');
        }

        return $this->error('发送失败，请稍后再试');
    }

    /**
     * 修改登录邮箱
     * @throws \Phalcon\Logger\Exception
     * @throws \Exception
     */
    public function changeEmailAction()
    {
        if ($this->request->isPost()) {
            $data = $this->request->getPost();
            MyData::mustHasSet($data, ['email', 'vercode']);

            $code = SmsCodeService::checkChangeAccountCode(
                $this->loginUser()->id,
                $data['email'],
                $data['vercode']
            );

            $user = $this->loginUser();
            Transaction::db(function () use ($user, $data, $code) {
                $user->email = $data['email'];
                $user->email_at = time();
                $user->email_valid = 1;
                if ($user->save() === false) {
                    throw new LogException('更新邮箱状态失败', ['user' => $user->toArray(), 'err' => $user->getErrors()]);
                }
                SmsCodeService::done($code);
            });
            TaoAppService::loginUserHelper()->updateUserInfo($user->toArray());
            TaoAppService::loginAuthHelper()->getAdapter()->saveUser(
                TaoAppService::loginUserHelper()->user()
            );
            return $this->success('修改邮箱成功');
        }

        return [
        ];
    }

    /**
     * 发送邮箱验证码
     * @throws \Phalcon\Logger\Exception
     */
    public function emailCodeAction()
    {
        $this->mustPostMethod();
        $data = $this->request->getPost();
        MyData::mustHasSet($data, ['email']);

        $user = $this->loginUser();
        UserService::mustAllowChangeAccount('email', $data['email'], $user);

        if (SmsCodeService::sendChangeAccountCode(
            $user->id,
            $data['email'],
            $this->smsConfig,
        )) {
            return '验证码已发送';
        }
        return '发送失败，请稍后再试';
    }

    /**
     * 用户菜单
     * @throws \Exception
     */
    public function menuAction()
    {
        $ms = TaoAppService::loginUserHelper();
        $data = [
            'logoInfo' => [
                'title' => AppService::config()->getString('app.title'),
                'image' => AppService::config()->getString('app.logo'),
                'href' => AppService::urlModule('tao')
            ],
            'homeInfo' => $ms->getHomeInfo(),
            'menuInfo' => $ms->getMenuTree(),
        ];

        $this->json($data);
    }

    /**
     * 修改密码
     */
    public function passwordAction()
    {
        $user = $this->loginUser();
        if ($this->request->isPost()) {
            $password = $this->getRequest('password');
            $oldPassword = $this->getRequest('old_password');

            // 已有密码时必须验证旧密码，无密码（第三方登录用户）允许直接设置
            if ($user->password) {
                if (empty($oldPassword)) {
                    return $this->error('必须提供旧密码');
                }
                UserService::checkPassword($oldPassword, $user);
            }

            UserService::newPassword($password, $user);
            if ($user->save()) {
                return $this->success('修改密码成功');
            } else {
                return $this->error('修改密码失败');
            }
        }
        $this->htmlTitle = '修改密码';
        return [];
    }

    /**
     * 清除个人缓存
     */
    public function clearAction()
    {
        $userId = TaoAppService::loginUserHelper()->userId();
        TaoAppService::loginAuthHelper()->loginWith($userId);
        return $this->success('清除缓存成功');
    }

    /**
     * web 退出登录
     * 小程序退出登录在 /api/m/tao.open/user/logout  A0/open/Controllers/UserController.logoutAction
     */
    public function logoutAction()
    {
        TaoAppService::loginAuthHelper()->logout();;
        return $this->success('退出登录成功', '/');
    }


}