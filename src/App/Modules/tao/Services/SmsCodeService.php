<?php

namespace App\Modules\tao\Services;

use App\Modules\tao\Config\Config;
use App\Modules\tao\Helper\MessageHelper;
use App\Modules\tao\Helper\MyMvcHelper;
use App\Modules\tao\Models\SystemSmsCode;
use App\Modules\tao\Models\SystemUser;
use App\Modules\tao\sdk\EmailDriverInterface;
use App\Modules\tao\sdk\SmsDriverInterface;
use Phax\Support\Exception\BusinessException;
use Phax\Support\Exception\LogException;

class SmsCodeService
{
    static array $code = [
        // 用户注册验证码： 验证码${code}，您正在注册成为新用户，感谢您的支持！
        'register' => 'SMS_177005595',
        // 登录确认验证码： 验证码${code}，您正在登录，若非本人操作，请勿泄露。
        'login' => 'SMS_177005597',
        // 修改密码验证码： 验证码${code}，您正在尝试修改登录密码，请妥善保管账户信息
        'password' => 'SMS_177005594',
        // 信息变更验证码:  验证码${code}，您正在尝试变更重要信息，请妥善保管账户信息。
        'change_account' => 'SMS_177005593',
    ];
    /**
     * 每个注册账号每天允许发送的验证码数量
     */
    static int $maxRegisterCodeNum = 3;
    /**
     * 用户修改手机号+电子邮件每天允许发送验证码数量
     */
    static int $maxChangeAccountCodeNum = 3;
    /**
     * 登录验证码发送的次数
     */
    static int $maxSigninCodeNum = 3;
    static int $maxResetPasswordCodeNum = 3;

    public static function smsTemplateCode($kind): string
    {
        if (isset(static::$code[$kind])) {
            return static::$code[$kind];
        } else {
            throw new BusinessException('没有找到符合条件的短信模板:' . $kind);
        }
    }

    public function __construct(public MyMvcHelper $mvc)
    {
        // todo read the static::$code from config
    }

    /**
     * 默认的短信/邮件配置
     * @return array
     */
    public function smsConfig(): array
    {
        return $this->mvc->configService()->groupRows('sms');
    }

    /**
     * 获取最近的发送
     * @param array $conditions
     * @return SystemSmsCode|null
     */
    public function getLast(array $conditions): SystemSmsCode|null
    {
        return SystemSmsCode::queryBuilder($this->mvc->getDi())
            ->where($conditions)
            ->orderBy('id desc')
            ->findFirstModel();
    }

    /**
     * 统计当天发送的数量
     * @param array $conditions
     * @return int
     */
    public function todayCount(array $conditions): int
    {
        $beginOfDay = strtotime('today', time());
        return SystemSmsCode::queryBuilder($this->mvc->getDi())
            ->where($conditions)
            ->and('created_at >= ' . $beginOfDay, true)
            ->and('created_at <= ' . strtotime('tomorrow', $beginOfDay), true)
            ->count();
    }

    /**
     * @param EmailDriverInterface|SmsDriverInterface $engine
     * @param SystemSmsCode $code
     * @param $rst
     * @return bool
     * @throws \Phalcon\Logger\Exception
     */
    public function updateSendStatus(SmsDriverInterface|EmailDriverInterface $engine, SystemSmsCode $code, $rst): bool
    {
        $code->send_engine = $engine->engine();
        if ($engine->isSendSuccess($rst)) {
            $code->send_status = SystemSmsCode::SendStatusSuccess;
            if ($code->save() === false) {
                throw new LogException('更新验证码发送成功状态错误', ['id' => $code->id, 'err' => $code->getErrors()]);
            }
            return true;
        } else {
            $code->send_status = SystemSmsCode::SendStatusFailed;
            if ($code->save() === false) {
                throw new LogException('更新验证码发送失败状态错误', ['id' => $code->id, 'err' => $code->getErrors()]);
            }
            return false;
        }
    }

    /**
     * 比较用户提交的验证码是否正确
     */
    public function compare(SystemSmsCode $code, string $verCode): bool
    {
        if (empty($verCode)) {
            throw new BusinessException('必须填写验证码');
        }
        if ($code->code !== $verCode) {
            $code->num += 1;
            // 错误次数达到上限后立即标记为已使用，防止反复尝试
            if ($code->num >= Config::$verifyCodeMaxErrorNum) {
                $code->status = SystemSmsCode::StatusDone;
            }
            if ($code->save() === false) {
                throw new LogException('更新验证码错误次数失败', ['id' => $code->id, 'err' => $code->getMessages()]);
            }
            return false;
        }
        return true;
    }

    /**
     * 检查是否是一个合法的接收账号
     * @param string $receiver 接收账号
     * @return bool 是否为 email账号
     */
    public function mustReceiver(string $receiver): bool
    {
        if (empty($receiver)) {
            throw new BusinessException('接收账号不能为空');
        }
        $isPhone = $this->mvc->validate()->isPhone($receiver);
        $isEmail = $this->mvc->validate()->isEmail($receiver);
        if (!$isPhone && !$isEmail) {
            throw new BusinessException('只支持手机号或电子邮箱', [
                'receive' => $receiver,
            ]);
        }
        return $isEmail;
    }

    /**
     * 校验指定类型的验证码
     * @param string $account
     * @param string $verCode
     * @param string $kind
     * @return SystemSmsCode
     */
    public function checkCode(string $account, int $userId, string $kind, string $verCode): SystemSmsCode
    {
        if (empty($verCode)) {
            throw new BusinessException('必须填写验证码');
        }
        $this->mustReceiver($account);
        $condition = [
            'receiver' => $account,
            'user_id' => $userId,
            'kind' => $kind,
        ];
        $code = $this->getLast($condition);
        if (!$code || !$code->isActive()) {
            throw new BusinessException('验证码不存在或者已经过期了', [
                'condition' => $condition,
                'verCode' => $verCode,
                'code' => $code?->toArray(),
            ]);
        }
        if (!$this->compare($code, $verCode)) {
            throw new BusinessException('验证码错误', [
                'condition' => $condition,
                'verCode' => $verCode,
                'code' => $code->toArray(),
            ]);
        }
        return $code;
    }

    /**
     * 检验注册验证码
     * @param string $account
     * @param string $verCode
     * @return SystemSmsCode
     * @throws \Exception
     */
    public function checkRegisterCode(string $account, string $verCode): SystemSmsCode
    {
        return $this->checkCode($account, 0, 'register', $verCode);
    }

    /**
     * 发送注册验证码
     * @param string $account 账号
     * @return bool
     * @throws \Phalcon\Logger\Exception
     */
    public function sendRegisterCode(string $account, array $config = []): bool
    {
        $isEmail = $this->mustReceiver($account);
        // 检查发送
        $condition = [
            'user_id' => 0,
            'kind' => 'register',
            'receiver' => $account,
        ];
        // 从库中检查最近发送
        $history = $this->getLast($condition);
        if ($history && $history->isActive()) {
            return true;
        }

        $count = self::todayCount($condition);
        if ($count > self::$maxRegisterCodeNum) {
            throw new LogException('每天至多发送' . self::$maxRegisterCodeNum . '次注册验证码', [
                'condition' => $condition,
                'err' => '验证码发送次数达到上限'
            ]);
        }

        $mSer = new MessageHelper(empty($config) ? self::smsConfig() : $config);
        $code = $this->insertOne($condition, $this->mvc);

        if ($isEmail) {
            $email = $mSer->email();
            $rst = $email->useSingleSendMailRequest()
                ->setSubject('账号注册验证码')
                ->setAddress($account)
                ->setHtmlBody('您好，当前账号注册验证码为：' . $code->code)
                ->send();
            return $this->updateSendStatus($email, $code, $rst);
        } else {
            $sms = $mSer->sms();
            $rst = $sms->addTemplateCode(self::smsTemplateCode('register'))
                ->addPhoneNumber($account)
                ->addTemplateParams(['code' => $code->code])
                ->send();
            return $this->updateSendStatus($sms, $code, $rst);
        }
    }

    public function insertOne(array $condition, MyMvcHelper $mvc = null): SystemSmsCode
    {
        if (empty($condition['kind']) || empty($condition['receiver'])) {
            throw new BusinessException('必须指定 kind 和 receiver');
        }
        $verifyCode = rand(1000, 9999);
        $code = new SystemSmsCode();

        if (is_null($mvc)) {
            $mvc = $this->mvc;
        }
        $isEmail = $mvc->validate()->isEmail($condition['receiver']);

        $code->assign(array_merge($condition, [
            'status' => SystemSmsCode::StatusNew,
            'send_at' => time(),
            'code' => (string)$verifyCode,
            'ip' => $mvc->request()->getClientAddress(),
            'receiver_kind' => $isEmail
                ? SystemSmsCode::ReceiverKindEmail
                : SystemSmsCode::ReceiverKindPhone,
        ]));
        if ($code->create() === false) {
            throw new LogException('更新验证码记录错误', [
                'condition' => $condition,
                'err' => $code->getFirstError()
            ]);
        }
        return $code;
    }

    public function getMessageHelper(array $config = []): MessageHelper
    {
        return new MessageHelper(empty($config) ? self::smsConfig() : $config);
    }

    /**
     * 发送修改账号验证码
     * @param int $userId 用户 ID
     * @param string $account 新的账号
     * @return bool
     * @throws \Phalcon\Logger\Exception
     */
    public function sendChangeAccountCode(int $userId, string $account, array $config = [])
    {
        $isEmail = $this->mustReceiver($account);

        // 从库中检查最近发送
        $condition = [
            'user_id' => $userId,
            'kind' => 'change-account',// 'receiver' => $account,
        ];
        $history = $this->getLast($condition);
        if ($history && $history->isActive()) {
            return true;
        }

        $count = self::todayCount($condition);
        if ($count >= self::$maxChangeAccountCodeNum) {
            throw new BusinessException('每天至多发送' . self::$maxChangeAccountCodeNum . '次账号修改验证码');
        }
        $condition['receiver'] = $account;
        $mSer = $this->getMessageHelper($config);
        $code = $this->insertOne($condition, $this->mvc);

        if ($isEmail) {
            $email = $mSer->email();
            $rst = $email->useSingleSendMailRequest()
                ->setSubject('账号修改验证码')
                ->setAddress($account)
                ->setHtmlBody('您好，当前修改邮箱账号验证码为：' . $code->code)
                ->send();
            return $this->updateSendStatus($email, $code, $rst);
        } else {
            $sms = $mSer->sms();
            $rst = $sms->addTemplateCode(self::smsTemplateCode('change_account'))
                ->addPhoneNumber($account)
                ->addTemplateParams(['code' => $code->code])
                ->send();

            return $this->updateSendStatus($sms, $code, $rst);
        }
    }

    /**
     * 修改账号验证码校验
     * @param int $userId
     * @param string $account
     * @param string $verCode
     * @return SystemSmsCode
     * @throws \Exception
     */
    public function checkChangeAccountCode(int $userId, string $account, string $verCode): SystemSmsCode
    {
        return $this->checkCode($account, $userId, 'change-account', $verCode);
    }

    /**
     * 将验证码标记为已使用
     * @param SystemSmsCode $code
     * @return void
     */
    public function done(SystemSmsCode $code): void
    {
        $code->status = SystemSmsCode::StatusDone;
        if ($code->save() === false) {
            throw new LogException('更新验证码状态错误', $code->getErrors());
        }
    }

    /**
     * 发送登录验证码
     */
    public function sendLoginCode(string $account, array $config = []): bool
    {
        $isEmail = $this->mustReceiver($account);
        // 检查发送
        $condition = [
            'user_id' => 0,
            'kind' => 'login',
            'receiver' => $account,
        ];
        // 检查最新发送
        $history = $this->getLast($condition);
        if ($history && $history->isActive()) {
            return true;
        }

        $count = $this->todayCount($condition);
        if ($count > self::$maxSigninCodeNum) {
            throw new LogException('每天至多发送' . self::$maxSigninCodeNum . '次登录验证码', $condition);
        }

        $mSer = new MessageHelper(empty($config) ? self::smsConfig() : $config);
        $code = $this->insertOne($condition, $this->mvc);

        if ($isEmail) {
            $email = $mSer->email();
            $rst = $email->useSingleSendMailRequest()
                ->setSubject('账号登录验证码')
                ->setAddress($account)
                ->setHtmlBody('您好，您的登录验证码为：' . $code->code)
                ->send();
            return $this->updateSendStatus($email, $code, $rst);
        } else {
            $sms = $mSer->sms();
            $rst = $sms->addTemplateCode(self::smsTemplateCode('login'))
                ->addPhoneNumber($account)
                ->addTemplateParams(['code' => $code->code])
                ->send();
            return $this->updateSendStatus($sms, $code, $rst);
        }
    }

    /**
     * 登录验证码校验
     */
    public function checkLoginCode(string $account, string $verCode): SystemSmsCode
    {
        return $this->checkCode($account, 0, 'login', $verCode);
    }

    /**
     * 发送忘记密码邮件
     * @param $email
     * @param array $config
     * @return bool
     */
    public function sendForgotPasswordEmail($email, array $config = []): bool
    {
        if (!$this->mvc->validate()->isEmail($email)) {
            throw new BusinessException('不是有一个有效电子邮箱地址');
        }
        if ($row = SystemUser::queryBuilder($this->mvc->getDi())
            ->where(['email' => $email, 'email_valid' => 1])
            ->columns(['id', 'status'])->findFirstArray()) {
            $condition = ['user_id' => $row['id'], 'kind' => 'forgot', 'receiver' => $email];
            // 检查最新发送
            $history = $this->getLast($condition);
            if ($history && $history->isActive(3600 * 2)) {
                return true;
            }

            $count = $this->todayCount($condition);
            if ($count > self::$maxResetPasswordCodeNum) {
                throw new LogException('每天至多发送' . self::$maxResetPasswordCodeNum . '次重置密码邮件', $condition);
            }

            $mSer = new MessageHelper(empty($config) ? self::smsConfig() : $config);
            $code = $this->insertOne($condition, $this->mvc);


            $link = $this->mvc->urlWith('/m/tao/auth/password', [
                'type' => 'forgot',
                'sign' => md5($code->code . $row['id'] . $this->mvc->config()->path('app.key', 'tao-default-secret')),
                'id' => $code->id,
            ]);

            $body = <<<HTML
<p>您申请了重置登录密码！请在2小时内点击此链接以完成重置。</p>
<p><a href="{$link}" target="_blank">{$link}</a></p>
HTML;

            $engine = $mSer->email();
            $rst = $engine->useSingleSendMailRequest()
                ->setSubject('重置密码')
                ->setAddress($email)
                ->setHtmlBody($body)
                ->send();
            return $this->updateSendStatus($engine, $code, $rst);
        } else {
            throw new BusinessException('没有找到符合条件的账号');
        }
    }

    /**
     * 校验忘记密码邮件
     * @param int $id 记录 ID
     * @param string $sign 签名
     * @return SystemSmsCode
     */
    public function checkForgotPasswordEmail(int $id, string $sign): SystemSmsCode
    {
        if ($id < 1) {
            throw new BusinessException('验证码 ID 不能为空');
        }
        /**
         * @var $code SystemSmsCode
         */
        $code = SystemSmsCode::findFirst($id);
        if (!$code || !$code->isActive(3600 * 2)) {
            throw new BusinessException('重置密码验证码不存在或过期');
        }

        if ($sign !== md5($code->code . $code->user_id . $this->mvc->config()->path('app.key', 'tao-default-secret'))) {
            throw new BusinessException('签名参数不匹配');
        }
        if ($code->user_id < 1) {
            throw new BusinessException('验证码所绑定用户丢失');
        }
        return $code;
    }
}