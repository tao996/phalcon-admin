<?php

namespace App\Modules\tao\Helper;

use App\Modules\tao\sdk\captcha\ImageCaptcha;


/**
 * 验证码
 * @link https://www.google.com/recaptcha/about/ 谷歌验证码
 * @link https://forum.phalcon.io/discussion/10631/image-verification-code
 */
class CaptchaHelper
{

    private string $key = 'captcha';
    private bool $isTest = false;

    public function __construct(public MyMvcHelper $mvc)
    {
        $this->isTest = $this->mvc->isTest();
    }

    private function secret($text): string
    {
        return substr(md5(strtolower($text) . $this->key), 10, 10);
    }

    public function output(): void
    {
        $captcha = new ImageCaptcha();
        $captcha->create();
        if (!$this->isTest) {
            $this->mvc->session()->set($this->key, $this->secret($captcha->getText()));
        }
        $captcha->output();
        // exit;
    }

    /**
     * 验证码比较
     * @param string $code 用户填写的验证码
     * @param string $actual 期望的验证码，默认从 session 中获取
     * @return void
     * @throws \Exception
     */
    public function compare(string $code, string $actual = '', bool $destroy = true): void
    {
        if (empty($code)) {
            throw new \Exception('必须填写验证码', 200);
        }
        if (!$this->isTest) {
            if (empty($actual)) {
                $actual = $this->mvc->session()->get($this->key);
            } else {
                $actual = $this->secret($actual);
            }
            if (empty($actual) || strlen($actual) < 4) {
                throw new \Exception('验证码不存在');
            }
            $expect = $this->secret($code);
            if ($actual != $expect) {
                throw new \Exception('验证码错误', 200);
            }
        }
        if ($destroy) {
            $this->destroy();;
        }
    }

    public function destroy(): void
    {
        if (!$this->isTest) {
            $this->mvc->session()->remove($this->key);
        }
    }
}