<?php

namespace Phax\Bridge\Utils;

/*
 * Phalcon
src/phar-src/phalcon/Session/Manager.php 在 session start 的时候
调用了 session_set_save_handler($this->adapter);
src/phar-src/phalcon/Session/Adapter/AbstractAdapter.php
而 workerman 的 session 是直接保持为 json 数据，不一致，因此需要转换
修改 workerman/vendor/workerman/workerman/Protocols/Http/Session.php
在 __construct() 时 sessionUnserialize 数据，在 save() 时 sessionSerialize 数据
 */
class PhalconData
{
    /**
     * @param array $data
     * @return string
     */
    public static function sessionSerialize(array $data): string
    {
        if (empty($data)) {
            return '';
        }
        $sData = [];
        foreach ($data as $key => $value) {
            $serialized_value = serialize($value);
            $sData[] = $key . '|' . $serialized_value;
        }
        return serialize(join('', $sData));
    }


    public static function sessionUnserialize(string $text): array
    {
        if (empty($text)) {
            return [];
        }
        $text = unserialize($text);
        $pattern = '/;([\w-]+)\|/';
        preg_match_all($pattern, $text, $matches);
        $parts = [];
        if (empty($matches[0])) {
            if (empty($text)){
                return [];
            }
            self::explodeSessionItem($text, $parts);
            return $parts;
        }
        $preIndex = 0;
        foreach ($matches[0] as $splitKey) {
            $index = strpos($text, $splitKey, $preIndex) + 1;
            $uText = substr($text, $preIndex, $index - $preIndex);
            self::explodeSessionItem($uText, $parts);
            $preIndex = $index;
        }
        self::explodeSessionItem(substr($text, $preIndex), $parts);
        return $parts;
    }

    protected static function explodeSessionItem(string $item, array &$session): void
    {
        $uData = explode('|', $item, 2);
        $session[$uData[0]] = unserialize($uData[1]);
    }
}