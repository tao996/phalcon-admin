<?php

namespace App\Modules\tao\Controllers\user;

use App\Modules\tao\BaseController;
use Phax\Db\Transaction;
use Phax\Support\Logger;
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
            $this->vv->validate()->hostValidate($newHeadImg);
            $user->head_img = $newHeadImg;
            $user->signature = MyData::getString($data, 'signature');
            if ($user->save()) {
                $this->vv->loginUserHelper()->updateUserInfo([
                    'head_img' => $user->head_img,
                    'signature' => $user->signature,
                ]);
                return $this->success('保存成功');
            } else {
                return $this->error($user->getErrors());
            }
        }
        return [
            'roles' => $user->roles,
        ];
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

            $code = $this->vv->smsCodeService()->checkChangeAccountCode(
                $this->loginUser()->id,
                $data['phone'],
                $data['vercode']
            );

            $user = $this->loginUser();
            Transaction::db($this->vv->db(), function () use ($user, $data, $code) {
                $user->phone = $data['phone'];
                $user->phone_at = time();
                $user->phone_valid = 1;
                if ($user->save() === false) {
                    Logger::message('修改手机号失败', $user->getErrors());
                }
                $this->vv->smsCodeService()->done($code);
            });
            $this->vv->loginUserHelper()->updateUserInfo($user->toArray());
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
        $this->vv->userService()->mustAllowChangeAccount('phone', $data['phone'], $user);

        if ($this->vv->smsCodeService()->sendChangeAccountCode(
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

            $code = $this->vv->smsCodeService()->checkChangeAccountCode(
                $this->loginUser()->id,
                $data['email'],
                $data['vercode']
            );

            $user = $this->loginUser();
            Transaction::db($this->vv->db(), function () use ($user, $data, $code) {
                $user->email = $data['email'];
                $user->email_at = time();
                $user->email_valid = 1;
                if ($user->save() === false) {
                    Logger::message('修改邮箱失败', $user->getErrors());
                }
                $this->vv->smsCodeService()->done($code);
            });
            $this->loginUser->updateUserInfo($user->toArray());
            return $this->success('修改邮箱成功');
        }

        return [
        ];
    }

    /**
     * 发送邮箱验证码
     * @throws \Phalcon\Logger\Exception
     * @throws \Exception
     */
    public function emailCodeAction()
    {
        $this->mustPostMethod();
        $data = $this->request->getPost();
        MyData::mustHasSet($data, ['email']);

        $user = $this->loginUser();
        $this->vv->userService()->mustAllowChangeAccount('email', $data['email'], $user);

        if ($this->vv->smsCodeService()->sendChangeAccountCode(
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
        $ms = $this->vv->loginUserHelper();
        $data = [
            'logoInfo' => [
                'title' => $this->vv->config()->path('app.title'),
                'image' => $this->vv->config()->path('app.logo'),
                'href' => $this->vv->urlModule('tao')
            ],
            'homeInfo' => $ms->getHomeInfo(),
            'menuInfo' => $ms->getMenuTree(),
        ];

        return $this->json($data);
    }

    /**
     * 修改密码
     * @throws \Exception
     */
    public function passwordAction()
    {
        $user = $this->loginUser();
        if ($this->request->isPost()) {
            $password = $this->request->getPost('password');
            $this->vv->userService()->newPassword($password, $user);
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
        $this->vv->loginUserHelper()->reloadUserInfo();
        return $this->success('清除缓存成功');
    }

    /**
     * web 退出登录
     * 小程序退出登录在 /api/m/tao.open/user/logout  A0/open/Controllers/UserController.logoutAction
     */
    public function logoutAction()
    {
        $this->vv->loginAuthHelper()->logout();;
        return $this->success('退出登录成功', '/');
    }


}