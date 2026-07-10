<?php

namespace Phax\Utils;


use Phax\Support\Exception\BusinessException;

/**
 * 数据格式化
 * @link https://www.php.net/manual/zh/ref.array.php
 */
class MyData
{
    /**
     * 以路径方式来查询数组中的值
     * <code>
     * $data = ['a'=>'b'=>'c'=>1];
     * assertEquals(1, MyData::findWithPath($data,'a.b.c'));
     * </code>
     * @param array $data 待查询的数组
     * @param string $path 多层次使用 . 来分开，示例 a.b.c
     * @param mixed $default 默认值
     * @return mixed
     * @example
     */
    public static function findWithPath(array &$data, string $path, mixed $default = null): mixed
    {
        $keys = explode('.', $path);
        if (count($keys) == 1) {
            return empty($data[$path]) ? $default : $data[$path];
        }
        $current = $data;
        foreach ($keys as $key) {
            if (!isset($current[$key])) {
                return $default;
            }
            $current = $current[$key];
        }
        return empty($current) ? $default : $current;
    }

    /**
     * 使用指定键的傎来代替默认的索引 array_column 的扩展
     * <code>
     * $students = [ ['id' => 1, 'name' => 'a'], ['id' => 2, 'name' => 'b'], ['id' => 3, 'name' => 'c'] ];
     * $map = MyData::columnMap($students, 'id');
     * $this->assertEquals([
     *      1 => ['id' => 1, 'name' => 'a'],
     *      2 => ['id' => 2, 'name' => 'b'],
     *      3 => ['id' => 3, 'name' => 'c']
     * ], $map);
     * </code>
     * @param array $data
     * @param string|null|int $key 指定列名，如果为空，则直接返回 $data
     * @return array
     */
    public static function columnMap(array $data, string|null|int $key, bool $skipUnsetKey = false): array
    {
        if (is_null($key)) {
            return $data;
        }
        if (empty($data) || $key === '') {
            return [];
        }
        $rows = [];
        foreach ($data as $item) {
            if (!isset($item[$key])) {
                if ($skipUnsetKey) {
                    continue;
                }
                throw new BusinessException($key . ' is not exits in the data when columnMap');
            }
            $rows[$item[$key]] = (array)$item;
        }
        return $rows;
    }

    /**
     * 获取数组中指定键的值
     * @param array $data 數組
     * @param string|int|null $key 鍵
     * @param mixed $defValue 默認值
     * @return array|mixed|string
     */
    public static function get(array &$data, string|int|null $key, mixed $defValue = null): mixed
    {
        return key_exists($key, $data) ? $data[$key] : $defValue;
    }

    /**
     * 获取字符串；注意：0|null|undefined 都会被识别为空
     * @param array $data
     * @param string $key
     * @param string $def 默认值
     * @return string
     * @throws \Exception
     */
    public static function getString(array &$data, string $key, string $def = ''): string
    {
        if (empty($data[$key]) || in_array($data[$key], ['0', 'null', 'undefined'])) {
            return $def;
        } elseif (is_string($data[$key])) {
            return $data[$key];
        } else {
            throw new BusinessException('not a string value :' . $key);
        }
    }


    public static function getInt(array &$data, string $key, int $def = 0): int
    {
        return intval(self::get($data, $key, $def));
    }

    /**
     * 获取整数数组
     * <code>
     * $data = ['ids'=>['1','2','3']];
     * $data = ['ids'=>[1=>'on',2=>'on',3=>'on']];
     * $data = ['ids'=>'1,2,3'];
     * assertEquals([1,2,3], MyData::getIntsWith($data,'ids'));
     * </code>
     * @param array $data 待检测数组
     * @param string $key 数组中的键 ids
     * @return array [1,2,3]
     */
    public static function getIntsWith(array &$data, string $key): array
    {
        if (empty($data[$key])) {
            return [];
        }
        return self::getInts($data[$key]);
    }

    /**
     * 获取整数数组，如果不需要重复，可在获取结果后使用 array_unique 进行过滤
     * <code>
     * $data = ['1','2','3'];
     * $data = '1,2,3';
     * $data = [1=>'on',2=>'on',3=>'on']
     * assertEquals([1,2,3], MyData::getInts($data));
     * </code>
     * @param array|string $data
     * @return array [1,2,3]
     * @throws \Exception
     */
    public static function getInts(array|string $data): array
    {
        if (is_string($data)) {
            $items = explode(',', $data);
        } elseif (is_array($data)) {
            $valueInValue = isset($data[0]) && is_numeric($data[0]); // [1,2,3,] 格式
            if ($valueInValue) {
                $items = array_map('intval', $data);
            } else {
                $items = array_keys($data);
            }
        } else {
            throw new \Exception('unsupported params in MyData.getInts');
        }
        $rows = [];
        foreach ($items as $item) {
            if (filter_var($item, \FILTER_VALIDATE_INT)) {
                $rows[] = intval($item);
            } else {
                throw new BusinessException($item . ' is not a int value');
            }
        }
        return $rows;
    }

    /**
     * 提示数组中指定键的值（see test）
     * <code>
     * $data = ['a' => 1, 'b' => 2, 'c' => 'hello'];
     * $keys = ['a', 'c'];
     * assertEquals(['a' => 1, 'c' => 'hello'], MyData::getByKeys($data, $keys));
     * </code>
     * @param array|null $data 原始数组
     * @param array $keys 需要提取的键
     * @param array $intKeys 需要转换为整数的键，如果填写，会合并到 $keys
     * @return array 由 $keys 组成的新数组
     */
    public static function getByKeys(array|null &$data, array $keys, array $intKeys = []): array
    {
        if (empty($data)) {
            return [];
        }
        $keys = array_merge($keys, $intKeys);
        if (empty($keys)) {
            return $data;
        }
        // array_flip 交换数组的键和值
        // array_intersect_key 使用键名计算数组的交集
        $rst = array_intersect_key($data, array_flip($keys));
        foreach ($intKeys as $key) {
            if (isset($data[$key])) {
                $data[$key] = intval($data[$key]);
            }
        }
        return $rst;
    }

    /**
     * getByKeys 的同名方法
     * @param array|null $data
     * @param array $keys
     * @return array
     */
    public static function picker(array|null &$data, array $keys): array
    {
        return self::getByKeys($data, $keys);
    }

    /**
     * 获取第一个不为空的值
     * @param array $args
     * @param mixed $def 默认值
     * @return mixed
     */
    public static function firstValue(array $args, mixed $def): mixed
    {
        foreach ($args as $arg) {
            if (!empty($arg)) {
                return $arg;
            }
        }
        return $def;
    }

    /**
     * 文本截取，超过指定长度的文字则使用 ... 代替
     * @link https://stackoverflow.com/questions/11434091/add-if-string-is-too-long-php
     * @param string $text
     * @param int $length
     * @return string
     */
    public static function subtext(string $text, int $length): string
    {
        if (mb_strlen($text, 'utf8') > $length) {
            return mb_substr($text, 0, $length, 'utf8') . '...';
        } else {
            return $text;
        }
    }

    /**
     * 使用 \r\n 作为分割符，通常用于切割 textarea 内容
     * @link https://stackoverflow.com/questions/7058168/explode-textarea-php-at-new-lines
     * @param string $content
     * @return array
     */
    public static function splitLine(string $content): array
    {
        return preg_split('/\r\n|[\r\n]/', $content);
    }

    /**
     * 切割空格
     * @link https://stackoverflow.com/questions/1792950/explode-string-by-one-or-more-spaces-or-tabs
     * @param string $content
     * @return array
     */
    public static function splitSpace(string $content): array
    {
        return preg_split('/\s+/', $content, -1, PREG_SPLIT_NO_EMPTY);
    }


    /**
     * 通过值获取索引值
     * @param array $mapData [1=>'active', 2=>'disabled',...]
     * @param string|int $value 索引值所对应的值，如 'active'
     * @param mixed $def 默认值，默认为 null
     * @return mixed 索引值, 如 'active' 对应的索引值为 1
     */
    public static function getMapDataByValue(array $mapData, int|string $value, mixed $def = null): mixed
    {
        return array_flip($mapData)[$value] ?? $def;
    }

    /**
     * 通过索引值获取值
     * @param array $mapData
     * @param int|string $key
     * @param mixed $def 默认为  null
     * @return mixed
     */
    public static function getMapDataByKey(array $mapData, int|string $key, mixed $def = null): mixed
    {
        return $mapData[$key] ?? $def;
    }

}