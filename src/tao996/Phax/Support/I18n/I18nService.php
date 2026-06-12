<?php

namespace Phax\Support\I18n;

class I18nService
{
    private static array $langCache = [];
    private static array $namespaceCache = [];
    public static string $lang = 'zh_CN';

    /**
     * 核心翻译与替换引擎
     */
    public static function translate(string $key, string $default, array $params = [], string $namespace = ''): string
    {
        $translatedText = $default;

        // 1. 如果传了命名空间，尝试动态去对应模块/项目的 resources 目录下抓取语言包
        if (self::$lang != 'zh_CN' || !empty($namespace)) {
            $moduleName = self::extractModule($namespace);
            if ($moduleName) {
                $langData = self::loadModuleLang($moduleName);
                // 如果语言包里定义了该 key，则覆盖默认文字
                if (isset($langData[$key])) {
                    $translatedText = $langData[$key];
                }
            }
        }

        // 2. 🔍 核心：替换占位符 (支持 :name 或 @name，这里以 :name 为主，兼容 @name)
        if (!empty($params)) {
            foreach ($params as $placeholder => $value) {
                // 统一兼容处理：无论用户传的是 'name' 还是 ':name' 还是 '@name'
                $cleanKey = ltrim($placeholder, ':@$');

                // 准备好几种常见的占位符替换形式
                $searchArr = [
                    ':' . $cleanKey,
                    '@' . $cleanKey,
                    '{' . $cleanKey . '}'
                ];

                $translatedText = str_replace($searchArr, (string)$value, $translatedText);
            }
        }

        return $translatedText;
    }

    /**
     * 从命名空间中严格提取模块名 (复用你上一题的正则)
     */
    private static function extractModule(string $namespace): ?string
    {
        if (!isset(self::$namespaceCache[$namespace])) {
            $pattern = '/^App\\\\(Modules|Projects)\\\\([a-zA-Z0-9_]+)/';
            if (preg_match($pattern, $namespace, $matches)) {
                self::$namespaceCache[$namespace] = $matches[2]; // 返回 yihe 等
            }
        }
        return self::$namespaceCache[$namespace];
    }

    /**
     * 动态加载模块目录下的语言包（带静态缓存，防止重复读盘）
     */
    private static function loadModuleLang(string $moduleName): array
    {
        if (isset(self::$langCache[$moduleName])) {
            return self::$langCache[$moduleName];
        }

        // 拼接标准路径，例如: App/Modules/yihe/resources/lang/zh_CN.php
        // 注意：为防止 Windows 本地开发环境中文路径报错，确保项目目录名和语言包全英文
        $langFile = PATH_APP_MODULES . "{$moduleName}/resources/lang/" . self::$lang . ".php";

        if (file_exists($langFile)) {
            self::$langCache[$moduleName] = include $langFile;
        } else {
            self::$langCache[$moduleName] = [];
        }

        return self::$langCache[$moduleName];
    }
}