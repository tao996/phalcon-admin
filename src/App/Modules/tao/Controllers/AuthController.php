<?php

namespace App\Modules\tao\Controllers;

use App\Modules\tao\BaseController;
use App\Modules\tao\Models\SystemUser;
use App\Modules\tao\Services\SmsCodeService;
use App\Modules\tao\Services\UserService;
use App\Modules\tao\TaoAppService;
use App\Modules\tao\utils\RedirectUtil;
use Phax\Db\Transaction;
use Phax\Foundation\AppService;
use Phax\Support\Exception\BusinessException;
use Phax\Support\Exception\LogException;
use Phax\Utils\MyAssert;
use Phax\Utils\MyData;

class AuthController extends BaseController
{
    protected array|string $openActions = '*';
    protected string $htmlTitle = '注册登录';

    public function afterInitialize(): void
    {
        if ($this->isLogin()) {
            RedirectUtil::read();
        }
    }

    /**
     * 用户密码登录
     */
    public function indexAction(): array
    {
        if ($this->request->isPost()) {
            $data = $this->request->getPost();
            MyAssert::mustHasSet($data, ['account', 'password', 'captcha']);
            TaoAppService::captchaHelper()->compare($data['captcha']);
            /**
             * @var $user SystemUser
             */
            $user = null;
            if (AppService::isDemo()) {
                $admin = AppService::config()->getArray('app.demo.admin');
                if ($data['account'] == MyData::get($admin, 'account', 'admin')
                    && $data['password'] == MyData::get($admin, 'password', '123456')) {
                    $user = SystemUser::findFirst(1);
                }
            }

            if (!$user) {
                $user = UserService::loginWithPassword($data['account'], $data['password']);
            }

            $authResp = TaoAppService::loginAuthHelper()->getAdapter()->saveUser($user);
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
            MyAssert::mustHasSet($data, ['account', 'vercode']);
            $isEmail = SmsCodeService::mustReceiver($data['account']);
            SmsCodeService::checkLoginCode($data['account'], $data['vercode']);

            // 查询用户
            if ($user = SystemUser::queryBuilder($this->getDI())
                ->where([
                    $isEmail ? 'email' : 'phone' => $data['account'],
                    $isEmail ? 'email_valid' : 'phone_valid' => 1
                ])->findFirstModel()) {
                $token = $this->getLoginAdapter()->saveUser($user);
            } else {
                return $this->error('没有找到符合条件的账号');
            }

            TaoAppService::captchaHelper()->destroy();
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
        MyAssert::mustHasSet($data, ['captcha', 'account']);

        SmsCodeService::mustReceiver($data['account']);
        TaoAppService::captchaHelper()->compare($data['captcha']);

        // 账号检测
        try {
            UserService::mustCanLogin($data['account']);
        } catch (\Exception $e) {
            throw new LogException('账号异常', [
                'msg' => '待接收验证码的账号存在异常',
                'account' => $data['account'],
            ], previous: $e);
        }

        // 发送验证码
        if (!SmsCodeService::sendLoginCode($data['account'])) {
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
            MyAssert::mustHasSet($data, ['account', 'vercode', 'password']);

            UserService::mustAccountString($data['account']);
            UserService::mustCanRegister($data['account']);
            $code = SmsCodeService::checkRegisterCode($data['account'], $data['vercode']);

            // 账号注册
            Transaction::db(function () use ($data, $code) {
                $user = new SystemUser();
                UserService::newPassword($data['password'], $user);
                UserService::newAccount($data['account'], $user);
                if ($user->save() === false) {
                    throw new LogException('账号注册失败', [
                        'data' => $data, 'errors' => $user->getErrors()
                    ]);
                }

                SmsCodeService::done($code);
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
        MyAssert::mustHasSet($data, ['captcha', 'account']);

        UserService::mustAccountString($data['account']);
        TaoAppService::captchaHelper()->compare($data['captcha']);

        // TODO : ip 地址检查注册

        try {
            UserService::mustCanRegister($data['account']);
        } catch (\Exception $e) {
            throw new LogException('注册验证码已发送，请注意查收', [
                'msg' => '帐号注册失败',
                'data' => $data,
            ], previous: $e);
        }

        // 发送验证码
        if (!SmsCodeService::sendRegisterCode($data['account'])) {
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
            MyAssert::mustHasSet($data, ['account', 'captcha']);

            TaoAppService::captchaHelper()->compare($data['captcha']);

            SmsCodeService::sendForgotPasswordEmail($data['account']);

            return $this->success('重置密码邮件已发送，请注意查收');
        }
        return [
        ];
    }

    /**
     * 用户通过邮件链接重置密码
     */
    public function passwordAction()
    {
        $data = $this->request->getQuery();
        MyAssert::mustHasSet($data, ['type', 'sign', 'id']);
        if ('forgot' != $data['type']) {
            throw new BusinessException('参数错误');
        }
        $code = SmsCodeService::checkForgotPasswordEmail($data['id'], $data['sign']);

        if ($this->request->isPost()) {
            $d2 = $this->request->getPost();
            MyAssert::mustHasSet($d2, ['password']);
            UserService::mustPassword($d2['password']);
            $user = UserService::mustGetUser(['id' => $code->user_id]);
            UserService::newPassword($d2['password'], $user);
            if ($user->save() === false) {
                throw new LogException('重置密码失败', [
                    'errors' => $user->getErrors(),
                    'data' => $data, 'user' => $user->toArray()
                ]);
            }

            SmsCodeService::done($code);
            return $this->success('重置密码成功');
        }

        return [
        ];
    }


}