<?php

namespace Phax\Utils;


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
            return isset($data[$path]) && $data[$path] ? $data[$path] : $default;
        }
        $current = $data;
        foreach ($keys as $key) {
            if (!isset($current[$key])) {
                return $default;
            }
            $current = $current[$key];
        }
        return $current ?: $default;
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
                throw new \Exception($key . ' is not exits in the data when columnMap');
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
            throw new \Exception('not a string value :' . $key);
        }
    }

    /**
     * 必须是一个整数值
     * @param mixed $value 待检查的值
     * @return int
     * @throws \Exception
     */
    public function mustInt(mixed $value): int
    {
        if (!filter_var($value, FILTER_VALIDATE_INT)) {
            throw new \Exception($value . ' is not an integer');
        }
        return intval($value);
    }

    /**
     * 必须是一个整数集合
     * @param mixed $data 待检查的值，如果是字符串，则需要使用 ',' 分割
     * @return int[]
     * @throws \Exception
     */
    public function mustIntS(mixed $data): array
    {
        if (is_int($data)) {
            return [$data];
        } elseif (is_string($data)) {
            $data = explode(',', $data);
        } elseif (!is_array($data)) {
            throw new \Exception('data is not supported type for mustIntS');
        }

        foreach ($data as $k => $v) {
            if (is_int($v)) {
                $data[$k] = intval($v);
            } else {
                throw new \Exception($v . ' is not an integer');
            }
        }
        return $data;
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
                throw new \Exception($item . ' is not a int value');
            }
        }
        return $rows;
    }

    /**
     * 获取布尔值 : 字符串 (on|true|t|ok), >0 都将被作为 true 对待
     * @param array $data
     * @param string $key
     * @param bool $strict 是否严格类型，只接受 true/false
     * @return bool
     */
    public static function getBool(array $data, string $key, bool $strict = false): bool
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
        if ($strict) {
            return $v === true || $v === false;
        }
        if (is_numeric($v)) {
            return intval($v) > 0;
        }
        return in_array(strtolower($v), ['on', 'true', 't', 'ok', 1, '1']);
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
     * @throws \Exception
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
                throw new \Exception($key . ' is not exits in the data when mustHasSet');
            }
            if ($allYes) {
                if (empty($data[$key])) {
                    throw new \Exception($key . ' is not allow empty when mustHasSet');
                }
            } elseif (!in_array($key, $allowEmpty) && empty($data[$key])) {
                throw new \Exception($key . ' is not allow empty when mustHasSet');
            }
        }
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
     * 通常用在 Model 中
     * @param array $mapData [1=>'active', 2=>'disabled',...]
     * @param int|string|null $key 用戶提交的數據
     * @param mixed $def 默认值，默认为 null 表示没有找到则抛出异常
     * @return mixed
     */
    public static function getMapData(array $mapData, int|null|string $key = 0, mixed $def = null): mixed
    {
        if (empty($key)) {
            return $mapData;
        }
        if (isset($mapData[$key])) {
            return $mapData[$key];
        } elseif (is_null($def)) {
            throw new \Exception(sprintf('key:(%s) is not found in mapData', $key));
        }
        return $def;
    }

}