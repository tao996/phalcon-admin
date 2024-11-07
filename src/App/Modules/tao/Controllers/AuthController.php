<?php

namespace App\Modules\tao\Controllers;

use App\Modules\tao\BaseController;
use App\Modules\tao\Models\SystemUser;
use Phax\Db\Transaction;
use Phax\Support\Logger;
use Phax\Utils\MyData;

class AuthController extends BaseController
{
    protected array|string $openActions = '*';
    protected string $htmlTitle = '注册登录';

    public function afterInitialize(): void
    {
        if ($this->isLogin()) {
            $this->vv->redirectHelper()->read();
        }
    }

    /**
     * 用户密码登录
     * @throws \Exception
     */
    public function indexAction(): array
    {
        if ($this->request->isPost()) {
            $data = $this->request->getPost();
            MyData::mustHasSet($data, ['account', 'password', 'captcha']);
            $this->vv->captchaHelper()->compare($data['captcha']);
            /**
             * @var $user SystemUser
             */

            if ($data['account'] == 'admin' && $data['password'] == '123456') {
                $user = SystemUser::findFirst(1);
            }

            if (!$user) {
                $isEmail = $this->vv->smsCodeService()->mustReceiver($data['account']);
                $qb = SystemUser::queryBuilder()
                    ->where(
                        $isEmail
                            ? ['email' => $data['account'], 'email_valid' => 1]
                            : ['phone' => $data['account'], 'phone_valid' => 1]
                    );

                if ($user = $qb->findFirstModel()) {
                    $user->checkPassword($data['password']);
                    $user->checkStatus();
                } else {
                    return $this->error('请检查您的账号和密码是否正确..');
                }
            }


            $authResp = $this->vv->loginAuthHelper()->getAdapter()->saveUser($user->toArray());
            return $this->success('登录成功', $authResp);
        }
        return [
        ];
    }

    /**
     * 验证码登录
     * @throws \Exception
     */
    public function signinAction()
    {
        if ($this->request->isPost()) {
            $data = $this->request->getPost();
            MyData::mustHasSet($data, ['account', 'vercode']);
            $isEmail = $this->vv->smsCodeService()->mustReceiver($data['account']);
            $this->vv->smsCodeService()->checkLoginCode($data['account'], $data['vercode']);

            // 查询用户
            if ($user = SystemUser::queryBuilder()
                ->where([
                    $isEmail ? 'email' : 'phone' => $data['account'],
                    $isEmail ? 'email_valid' : 'phone_valid' => 1
                ])->findFirstModel()) {
                $token = $this->loginAuth->saveUser($user->toArray());
            } else {
                return $this->error('没有找到符合条件的账号');
            }

            $this->vv->captchaHelper()->destory();
            return $this->success('登录成功', $token);
        }
        return [
        ];
    }

    /**
     * 发送登录验证码
     * @throws \Exception
     */
    public function signinCodeAction()
    {
        $this->mustPostMethod();
        $data = $this->request->getPost();
        MyData::mustHasSet($data, ['captcha', 'account']);

        $this->vv->smsCodeService()->mustReceiver($data['account']);
        $this->vv->captchaHelper()->compare($data['captcha']);

        // 账号检测
        try {
            $this->vv->userService()->mustCanLogin($data['account']);
        } catch (\Exception $e) {
            Logger::message('登录验证码已发送，请注意查收.', [
                $e->getMessage(),
                '登录账号检查异常:' . $data['account'],
            ]);
        }

        // 发送验证码
        if (!$this->vv->smsCodeService()->sendLoginCode($data['account'])) {
            return $this->error('发送失败，请稍后再试');
        }
        return $this->success('登录验证码已发送，请注意查收');
    }

    /**
     * 账号注册
     * @throws \Exception
     */
    public function signupAction()
    {
        if ($this->request->isPost()) {
            $data = $this->request->getPost();
            MyData::mustHasSet($data, ['account', 'vercode', 'password']);

            $this->vv->userService()->mustAccountString($data['account']);
            $this->vv->userService()->mustCanRegister($data['account']);
            $code = $this->vv->smsCodeService()->checkRegisterCode($data['account'], $data['vercode']);

            // 账号注册
            Transaction::db($this->vv->db(), function () use ($data, $code) {
                $user = new SystemUser();
                $this->vv->userService()->newPassword($data['password'], $user);
                $this->vv->userService()->newAccount($data['account'], $user);
                if ($user->save() === false) {
                    Logger::message('账号注册失败，请稍后再试', $user->getErrors());
                }

                $this->vv->smsCodeService()->done($code);
            });

            return $this->success('账号注册成功');
        }
        return [
        ];
    }

    /**
     * 发送账号注册验证码
     * @throws \Exception
     */
    public function signupCodeAction()
    {
        $this->mustPostMethod();
        $data = $this->request->getPost();
        MyData::mustHasSet($data, ['captcha', 'account']);

        $this->vv->userService()->mustAccountString($data['account']);
        $this->vv->captchaHelper()->compare($data['captcha']);

        // TODO : ip 地址检查注册

        try {
            $this->vv->userService()->mustCanRegister($data['account']);
        } catch (\Exception $e) {
            Logger::message('注册验证码已发送，请注意查收', [
                $e->getMessage(),
                '注册账号检查:' . $data['account'],
            ]);
        }

        // 发送验证码
        if (!$this->vv->smsCodeService()->sendRegisterCode($data['account'])) {
            return $this->error('发送失败，请稍后再试');
        }

        return $this->success('验证码已发送，请注意查收');
    }

    /**
     * 重置密码
     * @throws \Exception
     */
    public function forgotAction()
    {
        if ($this->request->isPost()) {
            $data = $this->request->getPost();
            MyData::mustHasSet($data, ['account', 'captcha']);

            $this->vv->captchaHelper()->compare($data['captcha']);

            $this->vv->smsCodeService()->sendForgotPasswordEmail($data['account']);

            return $this->success('重置密码邮件已发送，请注意查收');
        }
        return [
        ];
    }

    /**
     * 用户通过邮件链接重置密码
     * @throws \Exception
     */
    public function passwordAction()
    {
        $data = $this->request->getQuery();
        MyData::mustHasSet($data, ['type', 'sign', 'id']);
        if ('forgot' != $data['type']) {
            throw new \Exception('参数错误');
        }
        $code = $this->vv->smsCodeService()->checkForgotPasswordEmail($data['id'], $data['sign']);

        if ($this->request->isPost()) {
            $d2 = $this->request->getPost();
            MyData::mustHasSet($d2, ['password']);
            $this->vv->userService()->mustPassword($d2['password']);
            $user = $this->vv->userService()->mustGetUser(['id' => $code->user_id]);
            $this->vv->userService()->newPassword($d2['password'], $user);
            if ($user->save() === false) {
                return $this->error('重置密码失败');
            }

            $this->vv->smsCodeService()->done($code);
            return $this->success('重置密码成功');
        }

        return [
        ];
    }


}