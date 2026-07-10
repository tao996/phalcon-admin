<?php

namespace App\Modules\tao\Helper;

use App\Modules\tao\sdk\captcha\ImageCaptcha;
use Phax\Foundation\AppService;
use Phax\Support\Exception\BusinessException;
use Phax\Support\Logger;


/**
 * 验证码
 * @link https://www.google.com/recaptcha/about/ 谷歌验证码
 * @link https://forum.phalcon.io/discussion/10631/image-verification-code
 */
class CaptchaHelper
{

    private string $key = 'captcha';
    private bool $local_test = false;

    public function __construct()
    {
        $this->local_test = AppService::isTest() || AppService::isDemo();
        if ($this->local_test && !IS_DEBUG) {
            Logger::warning('验证码在非 DEBUG 模式下被跳过，请检查 isTest/isDemo 配置');
        }
    }

    private function secret($text): string
    {
        return substr(md5(strtolower($text) . $this->key), 10, 10);
    }

    public function output(): void
    {
        $captcha = new ImageCaptcha();
        $captcha->create();
        if (!$this->local_test) {
            AppService::session()->set($this->key, $this->secret($captcha->getText()));
        }
        $captcha->output(AppService::response());
    }

    /**
     * 验证码比较
     * @param string $code 用户填写的验证码
     * @param string $actual 期望的验证码，默认从 session 中获取
     * @return void
     */
    public function compare(string $code, string $actual = '', bool $destroy = true): void
    {
        if (empty($code)) {
            throw new BusinessException('必须填写验证码');
        }
        if (!$this->local_test) {
            if (empty($actual)) {
                $actual = AppService::session()->get($this->key);
            } else {
                $actual = $this->secret($actual);
            }
            if (empty($actual) || strlen($actual) < 4) {
                throw new BusinessException('验证码不存在');
            }
            $expect = $this->secret($code);
            if ($actual !== $expect) {
                throw new BusinessException('验证码错误', [
                    'code' => $code, 'expect' => $expect, 'actual' => $actual,
                ]);
            }
        }
        if ($destroy) {
            $this->destroy();;
        }
    }

    public function destroy(): void
    {
        if (!$this->local_test) {
            AppService::session()->remove($this->key);
        }
    }
}