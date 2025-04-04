<?php

namespace Phax\Utils;

use RuntimeException;
use stdClass;

/**
 * 翻译不规范（类似js格式)的 JSON 字符串
 * @link https://github.com/EFTEC/Services_JSON/blob/main/src/Services_JSON.php
 */
class ServicesJSON
{
    protected const SLICE = 1;
    protected const IN_STR = 2;
    protected const IN_ARR = 3;
    protected const IN_OBJ = 4;
    protected const IN_CMT = 5;
    /** @var int the value returned will be an array instead of a stdclass */
    public const GET_ARRAY = 16;
    /** @var int supresses the errors and replaces by a null */
    public const SUPPRESS_ERRORS = 32;
    /** @var int supports to-json */
    public const USE_TO_JSON = 64;
    /** @var int If the fix value is broken, then it adds [] or {} automatically */
    public const DECODE_FIX_ROOT = 128;
    public const DECODE_NO_QUOTE = 256;

    protected static $use = 0;
    // private - cache the mbstring lookup results..
    protected static $_mb_strlen = false;
    protected static $_mb_substr = false;
    protected static $_mb_convert_encoding = false;

    /**
     * constructs a new JSON instance. We force this library as static
     *
     */
    protected function __construct()
    {
    }

    protected static function init(?int $use = null): void
    {
        if ($use !== null) {
            self::$use = $use;
        }
        self::$_mb_strlen = function_exists('mb_strlen');
        self::$_mb_convert_encoding = function_exists('mb_convert_encoding');
        self::$_mb_substr = function_exists('mb_substr');
    }

    /**
     * convert a string from one UTF-16 char to one UTF-8 char
     *
     * Normally should be handled by mb_convert_encoding, but
     * provides a slower PHP-only method for installations
     * that lack the multibye string extension.
     *
     * @param string $utf16 UTF-16 character
     * @return   string  UTF-8 character
     * @access   private
     */
    protected static function utf162utf8($utf16): string
    {
        if (self::$_mb_convert_encoding) {
            return mb_convert_encoding($utf16, 'UTF-8', 'UTF-16');
        }
        $bytes = (ord($utf16[0]) << 8) | ord($utf16[1]);
        switch (true) {
            case ((0x7F & $bytes) == $bytes):
                // this case should never be reached, because we are in ASCII range
                // see: https://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return chr(0x7F & $bytes);
            case (0x07FF & $bytes) == $bytes:
                // return a 2-byte UTF-8 character
                // see: https://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return chr(0xC0 | (($bytes >> 6) & 0x1F)) . chr(0x80 | ($bytes & 0x3F));
            case (0xFFFF & $bytes) == $bytes:
                // return a 3-byte UTF-8 character
                // see: https://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return chr(0xE0 | (($bytes >> 12) & 0x0F)) . chr(0x80 | (($bytes >> 6) & 0x3F)) . chr(0x80 | ($bytes & 0x3F));
        }
        // ignoring UTF-32 for now, sorry
        return '';
    }

    /**
     * convert a string from one UTF-8 char to one UTF-16 char
     *
     * Normally should be handled by mb_convert_encoding, but
     * provides a slower PHP-only method for installations
     * that lack the multibye string extension.
     * @param string $utf8 UTF-8 character
     * @return   string  UTF-16 character
     * @access   private
     */
    public static function utf82utf16($utf8): string
    {
        if (self::$_mb_convert_encoding) {
            return mb_convert_encoding($utf8, 'UTF-16', 'UTF-8');
        }
        switch (self::strlen8($utf8)) {
            case 1:
                // this case should never be reached, because we are in ASCII range
                // see: https://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return $utf8;
            case 2:
                // return a UTF-16 character from a 2-byte UTF-8 char
                // see: https://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return chr(0x07 & (ord($utf8[0]) >> 2)) . chr((0xC0 & (ord($utf8[0]) << 6)) | (0x3F & ord($utf8[1])));
            case 3:
                // return a UTF-16 character from a 3-byte UTF-8 char
                // see: https://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return chr((0xF0 & (ord($utf8[0]) << 4)) | (0x0F & (ord($utf8[1]) >> 2))) . chr((0xC0 & (ord($utf8[1]) << 6)) | (0x7F & ord($utf8[2])));
        }
        // ignoring UTF-32 for now, sorry
        return '';
    }

    /**
     * encodes an arbitrary variable into JSON format (and sends JSON Header)
     *
     * @param mixed $var any number, boolean, string, array, or object to be encoded.
     *                           see argument 1 to Services_JSON() above for array-parsing behavior.
     *                           if var is a strng, note that encode() always expects it
     *                           to be in ASCII or UTF-8 format!
     *
     * @return   mixed   JSON string representation of input var or an error if a problem occurs
     * @access   public
     */
    public static function encode($var, $use = 0)
    {
        //header('Content-type: application/json');
        self::init($use);
        return self::encodeUnsafe($var);
    }

    /**
     * encodes an arbitrary variable into JSON format without JSON Header - warning - may allow XSS!!!!)
     *
     * @param mixed $var any number, boolean, string, array, or object to be encoded.
     *                           see argument 1 to Services_JSON() above for array-parsing behavior.
     *                           if var is a strng, note that encode() always expects it
     *                           to be in ASCII or UTF-8 format!
     *
     * @return   mixed   JSON string representation of input var or an error if a problem occurs
     * @access   public
     */
    public static function encodeUnsafe($var)
    {
        // see bug #16908 - regarding numeric locale printing
        $lc = setlocale(LC_NUMERIC, 0);
        setlocale(LC_NUMERIC, 'C');
        $ret = self::_encode($var);
        setlocale(LC_NUMERIC, $lc);
        return $ret;
    }

    /**
     * PRIVATE CODE that does the work of encodes an arbitrary variable into JSON format
     *
     * @param mixed $var any number, boolean, string, array, or object to be encoded.
     *                           see argument 1 to Services_JSON() above for array-parsing behavior.
     *                           if var is a strng, note that encode() always expects it
     *                           to be in ASCII or UTF-8 format!
     *
     * @return   mixed   JSON string representation of input var or an error if a problem occurs
     * @access   public
     */
    public static function _encode($var)
    {
        switch (gettype($var)) {
            case 'boolean':
                return $var ? 'true' : 'false';
            case 'NULL':
                return 'null';
            case 'integer':
                return (int)$var;
            case 'double':
            case 'float':
                return (float)$var;
            case 'string':
                // STRINGS ARE EXPECTED TO BE IN ASCII OR UTF-8 FORMAT
                $ascii = '';
                $strlen_var = self::strlen8($var);
                /*
                 * Iterate over every character in the string,
                 * escaping with a slash or encoding to UTF-8 where necessary
                 */
                for ($c = 0; $c < $strlen_var; ++$c) {
                    $ord_var_c = ord($var[$c]);
                    switch (true) {
                        case $ord_var_c == 0x08:
                            $ascii .= '\b';
                            break;
                        case $ord_var_c == 0x09:
                            $ascii .= '\t';
                            break;
                        case $ord_var_c == 0x0A:
                            $ascii .= '\n';
                            break;
                        case $ord_var_c == 0x0C:
                            $ascii .= '\f';
                            break;
                        case $ord_var_c == 0x0D:
                            $ascii .= '\r';
                            break;
                        case $ord_var_c == 0x22:
                        case $ord_var_c == 0x2F:
                        case $ord_var_c == 0x5C:
                            // double quote, slash, slosh
                            $ascii .= '\\' . $var[$c];
                            break;
                        case (($ord_var_c >= 0x20) && ($ord_var_c <= 0x7F)):
                            // characters U-00000000 - U-0000007F (same as ASCII)
                            $ascii .= $var[$c];
                            break;
                        case (($ord_var_c & 0xE0) == 0xC0):
                            // characters U-00000080 - U-000007FF, mask 110XXXXX
                            // see https://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                            if ($c + 1 >= $strlen_var) {
                                ++$c;
                                $ascii .= '?';
                                break;
                            }
                            $char = pack('C*', $ord_var_c, ord($var[$c + 1]));
                            ++$c;
                            $utf16 = self::utf82utf16($char);
                            $ascii .= sprintf('\u%04s', bin2hex($utf16));
                            break;
                        case (($ord_var_c & 0xF0) == 0xE0):
                            if ($c + 2 >= $strlen_var) {
                                $c += 2;
                                $ascii .= '?';
                                break;
                            }
                            // characters U-00000800 - U-0000FFFF, mask 1110XXXX
                            // see https://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                            $char = pack('C*', $ord_var_c, @ord($var[$c + 1]), @ord($var[$c + 2]));
                            $c += 2;
                            $utf16 = self::utf82utf16($char);
                            $ascii .= sprintf('\u%04s', bin2hex($utf16));
                            break;
                        case (($ord_var_c & 0xF8) == 0xF0):
                            if ($c + 3 >= $strlen_var) {
                                $c += 3;
                                $ascii .= '?';
                                break;
                            }
                            // characters U-00010000 - U-001FFFFF, mask 11110XXX
                            // see https://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                            $char = pack('C*', $ord_var_c, ord($var[$c + 1]), ord($var[$c + 2]), ord($var[$c + 3]));
                            $c += 3;
                            $utf16 = self::utf82utf16($char);
                            $ascii .= sprintf('\u%04s', bin2hex($utf16));
                            break;
                        case (($ord_var_c & 0xFC) == 0xF8):
                            // characters U-00200000 - U-03FFFFFF, mask 111110XX
                            // see https://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                            if ($c + 4 >= $strlen_var) {
                                $c += 4;
                                $ascii .= '?';
                                break;
                            }
                            $char = pack('C*', $ord_var_c, ord($var[$c + 1]), ord($var[$c + 2]), ord($var[$c + 3]), ord($var[$c + 4]));
                            $c += 4;
                            $utf16 = self::utf82utf16($char);
                            $ascii .= sprintf('\u%04s', bin2hex($utf16));
                            break;
                        case (($ord_var_c & 0xFE) == 0xFC):
                            if ($c + 5 >= $strlen_var) {
                                $c += 5;
                                $ascii .= '?';
                                break;
                            }
                            // characters U-04000000 - U-7FFFFFFF, mask 1111110X
                            // see https://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                            $char = pack('C*', $ord_var_c, ord($var[$c + 1]), ord($var[$c + 2]), ord($var[$c + 3]), ord($var[$c + 4]), ord($var[$c + 5]));
                            $c += 5;
                            $utf16 = self::utf82utf16($char);
                            $ascii .= sprintf('\u%04s', bin2hex($utf16));
                            break;
                    }
                }
                return '"' . $ascii . '"';
            case 'array':
                /*
                 * As per JSON spec if any array key is not an integer
                 * we must treat then the whole array as an object. We
                 * also try to catch a sparsely populated associative
                 * array with numeric keys here because some JS engines
                 * will create an array with empty indexes up to
                 * max_index which can cause memory issues and because
                 * the keys, which may be relevant, will be remapped
                 * otherwise.
                 *
                 * As per the ECMA and JSON specification an object may
                 * have any string as a property. Unfortunately due to
                 * a hole in the ECMA specification if the key is a
                 * ECMA reserved word or starts with a digit the
                 * parameter is only accessible using ECMAScript's
                 * bracket notation.
                 */ // treat as a JSON object
                if (is_array($var) && count($var) && (array_keys($var) !== range(0, count($var) - 1))) {
                    $properties = array_map([self::class, 'name_value'], array_keys($var), array_values($var));
                    foreach ($properties as $property) {
                        if (self::isError($property)) {
                            return $property;
                        }
                    }
                    return '{' . implode(',', $properties) . '}';
                }
                // treat it like a regular array
                $elements = array_map([self::class, '_encode'], $var);
                foreach ($elements as $element) {
                    if (self::isError($element)) {
                        return $element;
                    }
                }
                return '[' . implode(',', $elements) . ']';
            case 'object':
                // support toJSON methods.
                if ((self::$use & self::USE_TO_JSON) && method_exists($var, 'toJSON')) {
                    // this may end up allowing unlimited recursion, so we check the return value to make sure it's
                    // not got the same method.
                    $recode = $var->toJSON();
                    if (method_exists($recode, 'toJSON')) {
                        if ((self::$use & self::SUPPRESS_ERRORS)) {
                            return 'null';
                        }
                        self::Services_JSON_Error(get_class($var) . " toJSON returned an object with a toJSON method.");
                    }
                    return self::_encode($recode);
                }
                $vars = get_object_vars($var);
                $properties = array_map([self::class, 'name_value'], array_keys($vars), array_values($vars));
                foreach ($properties as $property) {
                    if (self::isError($property)) {
                        return $property;
                    }
                }
                return '{' . implode(',', $properties) . '}';
            default:
                if ((self::$use & self::SUPPRESS_ERRORS)) {
                    return 'null';
                }
                self::Services_JSON_Error(gettype($var) . " can not be encoded as JSON string");
        }
    }

    /**
     * array-walking function for use in generating JSON-formatted name-value pairs
     *
     * @param string $name name of key to use
     * @param mixed $value reference to an array element to be encoded
     *
     * @return   string  JSON-formatted name-value pair, like '"name":value'
     * @access   private
     */
    protected static function name_value($name, $value)
    {
        $encoded_value = self::_encode($value);
        if (self::isError($encoded_value)) {
            return $encoded_value;
        }
        return self::_encode((string)$name) . ':' . $encoded_value;
    }

    /**
     * reduce a string by removing leading and trailing comments and whitespace
     *
     * @param    $str    string      string value to strip of comments and whitespace
     *
     * @return   string  string value stripped of comments and whitespace
     * @access   private
     */
    protected static function reduce_string($str): string
    {
        $str = preg_replace([// eliminate single line comments in '// ...' form
            '#^\s*//(.+)$#m', // eliminate multi-line comments in '/* ... */' form, at start of string
            '#^\s*/\*(.+)\*/#Us', // eliminate multi-line comments in '/* ... */' form, at end of string
            '#/\*(.+)\*/\s*$#Us'], '', $str);
        // eliminate extraneous space
        return trim($str);
    }

    /**
     * decodes a JSON string into appropriate variable
     *
     * @param string $str JSON-formatted string
     * @param int $use object behavior flags; combine with boolean-OR possible values:<br>
     *                    - <b>self::GET_ARRAY</b>:  syntax creates associative arrays<br>
     *                    instead of objects in decode().<br>
     *                    - <b>self::SUPPRESS_ERRORS</b>:  error suppression.<br>
     *                    Values which can't be encoded (e.g. resources)<br>
     *                    appear as NULL instead of throwing errors.<br>
     *                    By default, a deeply-nested resource will<br>
     *                    bubble up with an error, so all return values<br>
     *                    from encode() should be checked with isError()<br>
     *                    - <b>self::USE_TO_JSON</b>:  call toJSON when serializing objects<br>
     *                    It serializes the return value from the toJSON call rather<br>
     *                    than the object it'self,  toJSON can return associative arrays,<br>
     *                    strings or numbers, if you return an object, make sure it does<br>
     *                    not have a toJSON method, otherwise an error will occur.<br>
     *                    -<b>self::DECODE_FIX_ROOT</b>: Fix the code if the root parenthesis are
     *                    missing<br> Example: "1,2,3" works as "[1,2,3]" and "a:1,b:2" works as "{a:1,b:2}"
     *
     * @return   array|bool|float|int|stdClass|string|null   number, boolean, string, array, or object<br>
     *                             corresponding to given JSON input string.<br>
     *                             See argument 1 to Services_JSON() above for object-output behavior.<br>
     *                             Note that decode() always returns strings<br>
     *                             in ASCII or UTF-8 format!<br>
     * @access       public
     */
    public static function decode($str, int $use = 0)
    {
        if ($use & self::DECODE_FIX_ROOT) {
            $str = trim($str);
            $firstChar = $str[0];
            if ($firstChar !== '{' && $firstChar !== '[') {
                // fixing a malformed json-u, if the json-u doesn't start with { [ and ends with ] } then it wraps it
                // note:: it will fail for a simple value su as "hello", so it will return ["hello"]
                $p0 = strpos($str, ':'); // content:2,content2:3 =>  {content:2,content2:3}
                $p0 = $p0 === false ? PHP_INT_MAX : $p0;
                $p1 = strpos($str, ','); // 2,a:3  => [2,a:3]
                $p1 = $p1 === false ? PHP_INT_MAX : $p1;
                if ($p0 < $p1) {
                    $str = '{' . $str . '}';
                } else {
                    $str = '[' . $str . ']';
                }
            }
        }
        self::init($use);
        return self::decode2($str);
    }

    /**
     * It is used internally.
     * @param $str
     * @return array|bool|float|int|stdClass|string|null
     *
     * @noinspection PhpUndefinedVariableInspection
     */
    protected static function decode2($str)
    {
        $str = self::reduce_string($str);
        switch (strtolower($str)) {
            case 'true':
                return true;
            case 'false':
                return false;
            case 'null':
                return null;
            default:
                $m = [];
                if (is_numeric($str)) {
                    // Lookie-loo, it's a number
                    // This would work on its own, but I'm trying to be
                    // good about returning integers where appropriate:
                    // return (float)$str;
                    // Return float or int, as appropriate
                    return ((float)$str == (integer)$str) ? (integer)$str : (float)$str;
                }
                if (preg_match('/^(["\']).*(\1)$/s', $str, $m) && $m[1] == $m[2]) {
                    // STRINGS RETURNED IN UTF-8 FORMAT
                    $delim = self::substr8($str, 0, 1);
                    $chrs = self::substr8($str, 1, -1);
                    $utf8 = '';
                    $strlen_chrs = self::strlen8($chrs);
                    for ($c = 0; $c < $strlen_chrs; ++$c) {
                        $substr_chrs_c_2 = self::substr8($chrs, $c, 2);
                        $ord_chrs_c = ord($chrs[$c]);
                        switch (true) {
                            case $substr_chrs_c_2 === '\b':
                                $utf8 .= chr(0x08);
                                ++$c;
                                break;
                            case $substr_chrs_c_2 === '\t':
                                $utf8 .= chr(0x09);
                                ++$c;
                                break;
                            case $substr_chrs_c_2 === '\n':
                                $utf8 .= chr(0x0A);
                                ++$c;
                                break;
                            case $substr_chrs_c_2 === '\f':
                                $utf8 .= chr(0x0C);
                                ++$c;
                                break;
                            case $substr_chrs_c_2 === '\r':
                                $utf8 .= chr(0x0D);
                                ++$c;
                                break;
                            case $substr_chrs_c_2 === '\\"':
                            case $substr_chrs_c_2 === '\\\'':
                            case $substr_chrs_c_2 === '\\\\':
                            case $substr_chrs_c_2 === '\\/':
                                if (($delim === '"' && $substr_chrs_c_2 !== '\\\'') || ($delim === "'" && $substr_chrs_c_2 !== '\\"')) {
                                    $utf8 .= $chrs[++$c];
                                }
                                break;
                            case preg_match('/\\\u[0-9A-F]{4}/i', self::substr8($chrs, $c, 6)):
                                // single, escaped unicode character
                                $utf16 = chr(hexdec(self::substr8($chrs, ($c + 2), 2))) . chr(hexdec(self::substr8($chrs, ($c + 4), 2)));
                                $utf8 .= self::utf162utf8($utf16);
                                $c += 5;
                                break;
                            case ($ord_chrs_c >= 0x20) && ($ord_chrs_c <= 0x7F):
                                $utf8 .= $chrs[$c];
                                break;
                            case ($ord_chrs_c & 0xE0) == 0xC0:
                                // characters U-00000080 - U-000007FF, mask 110XXXXX
                                //see https://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                $utf8 .= self::substr8($chrs, $c, 2);
                                ++$c;
                                break;
                            case ($ord_chrs_c & 0xF0) == 0xE0:
                                // characters U-00000800 - U-0000FFFF, mask 1110XXXX
                                // see https://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                $utf8 .= self::substr8($chrs, $c, 3);
                                $c += 2;
                                break;
                            case ($ord_chrs_c & 0xF8) == 0xF0:
                                // characters U-00010000 - U-001FFFFF, mask 11110XXX
                                // see https://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                $utf8 .= self::substr8($chrs, $c, 4);
                                $c += 3;
                                break;
                            case ($ord_chrs_c & 0xFC) == 0xF8:
                                // characters U-00200000 - U-03FFFFFF, mask 111110XX
                                // see https://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                $utf8 .= self::substr8($chrs, $c, 5);
                                $c += 4;
                                break;
                            case ($ord_chrs_c & 0xFE) == 0xFC:
                                // characters U-04000000 - U-7FFFFFFF, mask 1111110X
                                // see https://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                $utf8 .= self::substr8($chrs, $c, 6);
                                $c += 5;
                                break;
                        }
                    }
                    return $utf8;
                }
                if (preg_match('/^\[.*]$/s', $str) || preg_match('/^\{.*}$/s', $str)) {
                    // array, or object notation
                    if ($str[0] === '[') {
                        $stk = [self::IN_ARR];
                        $arr = [];
                    } else {
                        $stk = [self::IN_OBJ];
                        if (self::$use & self::GET_ARRAY) {
                            $obj = [];
                        } else {
                            $obj = new stdClass();
                        }
                    }
                    $stk[] = ['what' => self::SLICE, 'where' => 0, 'delim' => false];
                    $chrs = self::substr8($str, 1, -1);
                    $chrs = self::reduce_string($chrs);
                    if ($chrs == '') {
                        if (reset($stk) == self::IN_ARR) {
                            return $arr;
                        }
                        return $obj;
                    }
                    $strlen_chrs = self::strlen8($chrs);
                    for ($c = 0; $c <= $strlen_chrs; ++$c) {
                        $top = end($stk);
                        $substr_chrs_c_2 = self::substr8($chrs, $c, 2);
                        if (($c == $strlen_chrs) || (($chrs[$c] === ',') && ($top['what'] == self::SLICE))) {
                            // found a comma that is not inside a string, array, etc.,
                            // OR we've reached the end of the character list
                            $slice = self::substr8($chrs, $top['where'], ($c - $top['where']));
                            $stk[] = ['what' => self::SLICE, 'where' => ($c + 1), 'delim' => false];
                            if (reset($stk) == self::IN_ARR) {
                                // we are in an array, so just push an element onto the stack
                                $arr[] = self::decode2($slice);
                            } elseif (reset($stk) == self::IN_OBJ) {
                                // we are in an object, so figure out the property name and set an
                                // element in an associative array,for now
                                $parts = [];
                                /** @noinspection NotOptimalRegularExpressionsInspection */
                                if (preg_match('/^\s*(["\'].*[^\\\]["\'])\s*:/Uis', $slice, $parts)) {
                                    // "name":value pair
                                    $key = self::decode2($parts[1]);
                                    $val = self::decode2(trim(substr($slice, strlen($parts[0])), ", \t\n\r\0\x0B"));
                                    if (self::$use & self::GET_ARRAY) {
                                        $obj[$key] = $val;
                                    } else {
                                        $obj->$key = $val;
                                    }
                                } /** @noinspection NotOptimalRegularExpressionsInspection */ elseif (preg_match('/^\s*(\w+)\s*:/Ui', $slice, $parts)) {
                                    // name:value pair, where name is unquoted
                                    $key = $parts[1];
                                    $val = self::decode2(trim(substr($slice, strlen($parts[0])), ", \t\n\r\0\x0B"));
                                    if (self::$use & self::GET_ARRAY) {
                                        $obj[$key] = $val;
                                    } else {
                                        $obj->$key = $val;
                                    }
                                }
                            }
                        } elseif ((($chrs[$c] === '"') || ($chrs[$c] === "'")) && ($top['what'] != self::IN_STR)) {
                            // found a quote, and we are not inside a string
                            $stk[] = ['what' => self::IN_STR, 'where' => $c, 'delim' => $chrs[$c]];
                        } elseif (($chrs[$c] == $top['delim']) && ($top['what'] == self::IN_STR) && ((self::strlen8(self::substr8($chrs, 0, $c)) - self::strlen8(rtrim(self::substr8($chrs, 0, $c), '\\'))) % 2 != 1)) {
                            // found a quote, we're in a string, and it's not escaped
                            // we know that it's not escaped becase there is _not_ an
                            // odd number of backslashes at the end of the string so far
                            array_pop($stk);
                        } elseif (($chrs[$c] === '[') && in_array($top['what'], [self::SLICE, self::IN_ARR, self::IN_OBJ])) {
                            // found a left-bracket, and we are in an array, object, or slice
                            $stk[] = ['what' => self::IN_ARR, 'where' => $c, 'delim' => false];
                        } elseif (($chrs[$c] === ']') && ($top['what'] == self::IN_ARR)) {
                            // found a right-bracket, and we're in an array
                            array_pop($stk);
                        } elseif (($chrs[$c] === '{') && in_array($top['what'], [self::SLICE, self::IN_ARR, self::IN_OBJ])) {
                            // found a left-brace, and we are in an array, object, or slice
                            $stk[] = ['what' => self::IN_OBJ, 'where' => $c, 'delim' => false];
                        } elseif (($chrs[$c] === '}') && ($top['what'] == self::IN_OBJ)) {
                            // found a right-brace, and we're in an object
                            array_pop($stk);
                        } elseif (($substr_chrs_c_2 === '/*') && in_array($top['what'], [self::SLICE, self::IN_ARR, self::IN_OBJ])) {
                            // found a comment start, and we are in an array, object, or slice
                            $stk[] = ['what' => self::IN_CMT, 'where' => $c, 'delim' => false];
                            $c++;
                        } elseif (($substr_chrs_c_2 === '*/') && ($top['what'] == self::IN_CMT)) {
                            // found a comment end, and we're in one now
                            array_pop($stk);
                            $c++;
                            for ($i = $top['where']; $i <= $c; ++$i) {
                                $chrs = substr_replace($chrs, ' ', $i, 1);
                            }
                        }
                    }
                    if (reset($stk) == self::IN_ARR) {
                        return $arr;
                    }
                    if (reset($stk) == self::IN_OBJ) {
                        return $obj;
                    }
                }
                if (self::$use & self::DECODE_NO_QUOTE) {
                    return $str;
                }
        }
        return null;
    }

    protected static function isError($data, $code = null): bool
    {
        if (class_exists('pear')) {
            /** @noinspection PhpUndefinedClassInspection */
            return PEAR::isError($data, $code);
        }
        if (is_object($data) && (get_class($data) === 'services_json_error' || is_subclass_of($data, 'services_json_error'))) {
            return true;
        }
        return false;
    }

    /**
     * Calculates length of string in bytes
     * @param string $str
     * @return integer length
     */
    protected static function strlen8($str): int
    {
        if (self::$_mb_strlen) {
            return mb_strlen($str, "8bit");
        }
        return strlen($str);
    }

    /**
     * Returns part of a string, interpreting $start and $length as number of bytes.
     * @param string $string
     * @param integer $start start
     * @param integer $length length
     * @return string length
     */
    protected static function substr8($string, $start, $length = false): string
    {
        if ($length === false) {
            $length = self::strlen8($string) - $start;
        }
        if (self::$_mb_substr) {
            return mb_substr($string, $start, $length, "8bit");
        }
        return substr($string, $start, $length);
    }

    protected static function Services_JSON_Error($msg): void
    {
        throw new RuntimeException($msg);
    }

}