<?php

namespace Phax\Support\I18n;

use Phax\Support\Facade\MyHelperFacade;

class Lang
{

// https://docs.phalcon.io/5.0/en/i18n
    /**
     * @throws \IntlException
     */
    public static function message(string $lang, string $pattern, array $value): false|string
    {
        $formatter = new \MessageFormatter($lang, $pattern);
        return $formatter->format($value);
    }

    /**
     * 对文本进行替换
     * @link https://docs.phalcon.io/5.0/en/support-helper#interpolate
     * @param string $message ':date (YYYY-MM-DD)'
     * @param array $placeholders ['date'  => '2020-09-09']
     * @param string $leftToken 左分割符，为了保持跟 Laravel 之类的兼容，默认使用 : 号
     * @param string $rightToken
     * @return string '2020-09-09 (YYYY-MM-DD)'
     */
    public static function interpolate(
        string $message,
        array $placeholders = [],
        string $leftToken = ":",
        string $rightToken = ""
    ): string {
        return MyHelperFacade::interpolate($message, $placeholders, $leftToken, $rightToken);
    }

}