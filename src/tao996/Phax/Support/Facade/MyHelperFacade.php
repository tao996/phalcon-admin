<?php

namespace Phax\Support\Facade;


/**
 * 助手函数
 * @link https://docs.phalcon.io/5.0/en/support-helper
 * @method static array blacklist(array $collection, array $blackKeys) 剔除集合中指定的 keys 值
 * @method static array whitelist(array $collection, array $whiteKeys)
 * @method static array chunk(array $collection, int $size, bool $preserveKeys = false) 将集合按指定 size 切割
 * @method static mixed filter(array $collection, callable $method = null) 元素过滤
 * @method static mixed first(array $collection, callable $method = null) 获取集合的第一个元素
 * @method static mixed firstKey(array $collection, callable $method = null) 第一个元素的 key 值
 * @method static array flatten(array $collection, bool $deep = false) 打平
 * @method static mixed get(array $collection, mixed $index, mixed $defaultValue = null, string $cast = null) 通过键来获取值
 * @method static array group(array $collection, mixed $method) 没啥用：将子元素的值作为新数组中子元素的键，结果变成三维数组；你可能需要使用 MyData::resetRowsKey 来代替
 * @method static bool has(array $collection, mixed $index)
 * @method static bool isUnique(array $collection)
 * @method static mixed last(array $collection, callable $method = null)
 * @method static mixed lastKey(array $collection, callable $method = null)
 * @method static array order(array $collection, mixed $attribute, string $order = 'asc')
 * @method static array pluck(array $collection, string $element) 提取列值
 * @method static array set(array $collection, mixed $value, mixed $index = null)
 * @method static array sliceLeft(array $collection, int $elements = 1)
 * @method static array sliceRight(array $collection, int $elements = 1)
 * @method static array split(array $collection)
 * @method static object toObject(array $collection)
 * @method static bool validateAll(array $collection, callable $method)
 * @method static bool validateAny(array $collection, callable $method)
 * @method static string basename(string $uri, string $suffix = null) /etc/sudoers.d => .d
 * @method static string decode(string $data, bool $associative = false, int $depth = 512, int $options = 0)
 * @method static string encode($data, int $options = 0, int $depth = 512)
 * @method static string isBetween(int $value, int $start, int $end)
 * @method static string concat(string $delimiter, string $first, string $second, string ...$arguments) 拼接
 * @method static int countVowels(string $text) 元音字母（AEIOU）统计
 * @method static string decapitalize(string $text, bool $upperRest = false, string $encoding = 'UTF-8') 首字母小写
 * @method static string decrement(string $text, string $separator = '_')
 * @method static string increment(string $text, string $separator = '_')
 * @method static string dirFromFile(string $file) 为文件名计算合适的存储目录
 * @method static string dirSeparator(string $directory) 自动对目录路径进行合适化处理
 * @method static string dynamic(string $text, string $leftDelimiter = "{", string $rightDelimiter = "}", string $separator = "|") 随机字符串
 * @method static bool endsWith(string $haystack, string $needle, bool $ignoreCase = true)
 * @method static string firstBetween(string $text, string $start, string $end)
 * @method static bool includes(string $haystack, string $needle)
 * @method static string interpolate(string $message, array $context = [], string $leftToken = "%", string $rightToken = "%") 通常用来做为翻译
 * @method static bool isAnagram(string $first, string $second)
 * @method static bool isLower(string $text, string $encoding = 'UTF-8') 是否字谜
 * @method static bool isPalindrome(string $text) 是否回文
 * @method static bool isUpper(string $text, string $encoding = 'UTF-8')
 * @method static int len(string $text, string $encoding = 'UTF-8') 内部使用 mb_strlen
 * @method static string lower(string $text, string $encoding = 'UTF-8') 内部使用 mbstring
 * @method static string prefix($text, string $prefix)
 * @method static string random(int $type = 0, int $length = 8) 随机 RANDOM_ALNUM(0)[azAZ09]数字字母|RANDOM_ALPHA(1)[azAZ]字母|RANDOM_DISTINCT(5)验证码|RANDOM_HEXDEC(2)[0-9a-f]|RANDOM_NOZERO(4)[1-9]数字|RANDOM_NUMERIC(3)[0-9]数字
 * @method static string reduceSlashes(string $text) 剔除多余的斜杠，通常用于路径处理 'app/controllers//IndexController' => 'app/controllers/IndexController'
 * @method static string snakeCase(string $text, string $delimiters = null) 将 snake-case 转为 snake_case下划线风格 'customer-session' => 'customer_session'
 * @method static bool startsWith(string $haystack, string $needle, bool $ignoreCase = true)
 * @method static string suffix($text, string $suffix)
 * @method static string ucwords(string $text, string $encoding = 'UTF-8')
 * @method static string underscore(string $text) 空格转下划线
 * @method static string upper(string $text, string $encoding = 'UTF-8')
 * @method static string camelize(string $text, string $delimiters = null, bool $lowerFirst = false) 将字符串以分割符转为驼峰式命名风格(首字母小写） came-li-ze =》CameLiZe
 * @method static string friendly(string $text, string $separator = '-', bool $lowercase = true, mixed $replace = null) 将多个单词拼接成适合 SEO 地址 'This is a Test' => 'this-is-a-test'
 * @method static string humanize(string $text) friendly 的反操作，将 SEO 地址还原为单词 'kittens-are_cats' => 'kittens are cats'
 * @method static string kebabCase(string $text, string $delimiters = null)  kebab-case style 短横线命名法 'customer_session' => 'customer-session'
 * @method static string pascalCase(string $text, string $delimiters = null)
 * @method static string uncamelize(string $text, string $delimiters = '_')
 */
class MyHelperFacade extends Facade
{
    protected static function getFacadeName(): string
    {
        return 'helper';
    }

    protected static function getFacadeObject()
    {
        return new \Phalcon\Support\HelperFactory();
    }
}