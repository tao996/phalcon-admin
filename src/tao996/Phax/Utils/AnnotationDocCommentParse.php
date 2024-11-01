<?php

namespace Phax\Utils;


/**
 * 从 minime/annotations 中
 * @link https://github.com/marcioAlmada/annotations
 * 用于将注释内容转换为数组；
 */
class AnnotationDocCommentParse
{
    public const TOKEN_ANNOTATION_IDENTIFIER = '@';

    public const TOKEN_ANNOTATION_NAME = '[a-zA-Z\_\-\\\][a-zA-Z0-9\_\-\.\\\]*';

    protected static string $dataPattern = '/(?<=\\' . self::TOKEN_ANNOTATION_IDENTIFIER . ')('
    . self::TOKEN_ANNOTATION_NAME
    . ')(((?!\s\\' . self::TOKEN_ANNOTATION_IDENTIFIER . ').)*)/s';


    /**
     * 将注释内容转为数组，只有使用 @Xxx 的注释才会被提取出来
     * @param $docblock string 注释的内容
     * @return array
     */
    public static function parse($docblock)
    {
        $docblock = self::getDocblockTagsSection($docblock);
        $annotations = self::parseAnnotations($docblock);
        foreach ($annotations as &$value) {
            if (1 == count($value)) {
                $value = $value[0];
            }
        }

        return $annotations;
    }

    protected static function getDocblockTagsSection($docblock)
    {
        $docblock = self::sanitizeDocblock($docblock);
        preg_match('/^\s*\\' . self::TOKEN_ANNOTATION_IDENTIFIER . '/m', $docblock, $matches, PREG_OFFSET_CAPTURE);

        // return found docblock tag section or empty string
        return isset($matches[0]) ? substr($docblock, $matches[0][1]) : '';
    }

    protected static function sanitizeDocblock($docblock): array|string|null
    {
        return preg_replace('/\s*\*\/$|^\s*\*\s{0,1}|^\/\*{1,2}/m', '', $docblock);
    }

    protected static function parseAnnotations($str): array // str: "\@controller 菜单管理"
    {
        $annotations = [];
        preg_match_all(self::$dataPattern, $str, $found);
        foreach ($found[2] as $key => $value) {
            $annotations[self::sanitizeKey($found[1][$key])][] = self::dynamicTypeParse(trim($value), $found[1][$key]);
        }
        return $annotations;
    }

    protected static function sanitizeKey($key)
    {
        if (str_starts_with($key, '\\')) {
            $key = substr($key, 1);
        }

        return $key;
    }

    protected static function dynamicTypeParse($value, $annotation = null)
    {
//        if ('' === $value) return true; // 原来的类库，这里是返回 true
        if ('' === $value) return ''; // 修改为如果注释内容为空，则应该返回空字符串

        if (defined('JSON_PARSER_NOTSTRICT')) { // pecl-json-c ext
            $json = json_decode($value, false, 512, JSON_PARSER_NOTSTRICT);
        } else { // json-ext
            $json = json_decode($value);
        }

        if (JSON_ERROR_NONE === json_last_error()) {
            return $json;
        } elseif (false !== ($int = filter_var($value, FILTER_VALIDATE_INT))) {
            return $int;
        } elseif (false !== ($float = filter_var($value, FILTER_VALIDATE_FLOAT))) {
            return $float;
        }

        return $value;
    }
}