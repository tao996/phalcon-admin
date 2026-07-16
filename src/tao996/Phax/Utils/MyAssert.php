<?php

namespace Phax\Utils;

use Phax\Foundation\AppService;
use Phax\Support\Exception\BusinessException;
use Phax\Support\Validation\MobileCnValidation;

class MyAssert
{


    private static function hosts(): array
    {
        static $hosts = null;
        if (is_null($hosts)) {
            $hosts = AppService::config()->getArray('app.assets.hosts');
        }
        return $hosts;
    }

    public static function hostsValidate(array $urls): void
    {
        if (empty($urls)) {
            return;
        }
        $hosts = self::hosts();
        $hasHosts = !empty($hosts);
        foreach ($urls as $url) {
            $host = parse_url($url, PHP_URL_HOST);
            if ($hasHosts && !in_array($host, $hosts)) {
                throw new BusinessException(__('validate.host', '不允许的域名 :host', ['host' => $host]));
            }
        }
    }

    public static function hostValidate(string $url): void
    {
        if (empty($url)) {
            return;
        }
        $hosts = self::hosts();
        $host = parse_url($url, PHP_URL_HOST);
        if (!empty($hosts) && !in_array($host, $hosts)) {
            throw new BusinessException(__('validate.host', '不允许的域名 :host', ['host' => $host]));
        }
    }


    public static function mustEmail(string $email): void
    {
        if (!self::isEmail($email)) {
            throw new BusinessException(__('validate.email', ':field 不是一个有效的电子邮箱地址', ['field' => $email]));
        }
    }

    public static function isEmail(string $email): bool
    {
        if (!empty($email)) {
            return filter_var($email, FILTER_VALIDATE_EMAIL);
        }
        return false;
    }


    public static function mustPhone(string $phone): void
    {
        if (!self::isPhone($phone)) {
            throw new BusinessException(__('validate.cnPhone', ':field 不是一个有效的 +86 手机号码', ['field' => $phone]));
        }
    }

    public static function isPhone(string $phone): bool
    {
        if (!empty($phone)) {
            return MobileCnValidation::match($phone);
        }
        return false;
    }

    /**
     * 是否为真值
     * @param array $data
     * @param string $key
     * @param bool $strict 是否严格类型，只接受 true/false
     * @return bool
     */
    public static function isTrueWith(array $data, string $key, bool $strict = false): bool
    {
        $v = $data[$key] ?? false;
        return self::isTrue($v, $strict);
    }

    public static function isTrue($v, bool $strict = false): bool
    {
        if ($v) {
            return true;
        } else if (!$strict) {
            if (is_numeric($v)) {
                return intval($v) > 0;
            } elseif (is_string($v)) {
                return in_array(strtolower($v), ['on', 'true', 't', 'ok', '1']);
            }
        }
        return false;
    }

    public static function isBoolWith(array $data, string $key, bool $strict = false): bool
    {
        $v = $data[$key] ?? false;
        return self::isBool($v, $strict);
    }

    /**
     * 判断是否为布尔值
     * @param mixed $v 待判断的值
     * @param bool $strict 是否为严格类型，只接受 true/false
     * @return bool
     */
    public static function isBool(mixed $v, bool $strict = false): bool
    {
        if ($v === true || $v === false) {
            return true;
        }
        if (!$strict) {
            if (is_numeric($v)) {
                return intval($v) > 0;
            } elseif (is_string($v)) {
                return in_array(strtolower($v), ['on', 'true', 't', 'ok', '1']);
            }
        }
        return false;
    }

    public static function notEmpty(array $data, string $key): bool
    {
        return !empty($data[$key]);
    }

    /**
     * 必须有定义
     * @param array $data 待检查的数组
     * @param array $keys 数组中的值键，不能为空值
     * @param array $allowEmpty 允许空值的键，默认为空，表示全部不允许为空值；会合并到 $keys 中
     */
    public static function mustHasSet(array $data, array $keys, array $allowEmpty = []): void
    {
        $allYes = false;
        if (!empty($allowEmpty)) {
            $keys = array_merge($keys, $allowEmpty);
        } else {
            $allYes = true;
        }
        foreach ($keys as $key) {
            if (!array_key_exists($key, $data)) {
                throw new BusinessException($key . ' is not exits in the data when mustHasSet');
            }
            if ($allYes) {
                if (empty($data[$key])) {
                    throw new BusinessException($key . ' is not allow empty when mustHasSet');
                }
            } elseif (!in_array($key, $allowEmpty) && empty($data[$key])) {
                throw new BusinessException($key . ' is not allow empty when mustHasSet');
            }
        }
    }

    /**
     * 必须是一个整数值
     * @param mixed $value 待检查的值
     * @return int
     * @throws \Exception
     */
    public static function mustInt(mixed $value): int
    {
        if (!filter_var($value, FILTER_VALIDATE_INT)) {
            throw new BusinessException($value . ' is not an integer');
        }
        return intval($value);
    }

    /**
     * 必须是一个整数集合
     * @param mixed $data 待检查的值，如果是字符串，则需要使用 ',' 分割
     * @return int[]
     * @throws \Exception
     */
    public static function mustIntS(mixed $data): array
    {
        if (is_int($data)) {
            return [$data];
        } elseif (is_string($data)) {
            $data = explode(',', $data);
        } elseif (!is_array($data)) {
            throw new BusinessException('data is not supported type for mustIntS');
        }

        foreach ($data as $k => $v) {
            if (is_int($v)) {
                $data[$k] = intval($v);
            } else {
                throw new BusinessException($v . ' is not an integer');
            }
        }
        return $data;
    }

    /**
     * 是否为一个数字字符串
     * @param string|int $data
     * @return bool
     */
    public static function isNumberString(string|int $data): bool
    {
        return preg_match('/^[0-9]+$/', $data);
    }
}