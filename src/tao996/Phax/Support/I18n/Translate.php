<?php

namespace Phax\Support\I18n;


use Phax\Utils\MyFileSystem;

/**
 * phalcon 默认的翻译不支持二级数组
 * @link https://docs.phalcon.io/5.0/en/translate
 */
class Translate
{

    private static array $dictionary = []; // 翻译文件所在的目录
    private static array $i18nFiles = []; // 已经加载的翻译文件
    private static array $languages = []; // 需要翻译的语言

    public static array $messages = []; // 保存全部的翻译

    public function __construct(private readonly string $language = '')
    {
        $this->addLanguage($this->language);
        $this->addDictionary(__DIR__ . '/messages');
    }

    /**
     * 指定语言所在目录，目录下存放着语言文件，如：$path/en.php，$path/zh.php
     * @param string $languagesDir
     * @return self
     */
    public function addDictionary(string $languagesDir): self
    {
        if (!isset(self::$dictionary[$languagesDir])) {
            self::$dictionary[$languagesDir] = false;
        }
        return $this;
    }

    /**
     * 添加语言,用于替换掉 $pathOfDictionary 中的 :lang
     * @param string $language
     * @return self
     */
    public function addLanguage(string $language): self
    {
        if (!empty($language) && !in_array($language, self::$languages)) {
            self::$languages[] = $language;
        }
        return $this;
    }

    /**
     * 加载翻译
     * @param string $dir
     * @throws \Exception
     */
    private function loadDictionary(string $dir): void
    {
        if (empty($dir)) {
            throw new \Exception('path must not empty when load dictionary');
        }
        if (empty(self::$languages)) {
            $languageFiles = MyFileSystem::findInDirs($dir, 'file');
            foreach ($languageFiles as $file) {
                if (str_ends_with($file, '.php')) {
                    $lang = pathinfo($file, PATHINFO_FILENAME);
                    $this->loadLanguageFile($lang, MyFileSystem::fullpath($dir, $file));
                }
            }
        } else {
            foreach (self::$languages as $lang) {
                $filepath = MyFileSystem::fullpath($dir, $lang . '.php');
                $this->loadLanguageFile($lang, $filepath);
            }
        }
    }

    private function loadLanguageFile(string $language, string $filepath): void
    {
        if (empty($filepath)) {
            return;
        }
        if (file_exists($filepath)) {
            if (!in_array($filepath, self::$i18nFiles)) {
                $messages = require_once $filepath;
                if (!isset(self::$messages[$language])) {
                    self::$messages[$language] = [];
                }
                self::$messages[$language] = array_merge(self::$messages[$language], $messages);
                self::$i18nFiles[] = $filepath;
            }
        } else {
            throw new \Exception('language file not exist:' . $filepath);
        }
    }

    /**
     * 加载全部翻译
     * @return $this
     * @throws \Exception
     */
    public function load(): self
    {
        foreach (self::$dictionary as $dir => $load) {
            if (!$load) {
                self::loadDictionary($dir);
                self::$dictionary[$dir] = true;
            }
        }
        return $this;
    }

    /**
     * 获取翻译后的内容
     * @param string $language 语言
     * @param string $key 键
     * @param array $placeholders 替换词
     * @param string $defMessage 默认信息
     * @return string
     * @throws \Exception
     */
    public static function get(string $language, string $key, array $placeholders = [], string $defMessage = ''): string
    {
        $message = self::$messages[$language][$key] ?? $defMessage;
        if (empty($message)) {
            throw new \Exception('could not find (' . $key . ') in the language (' . $language . ') file');
        }
        return Lang::interpolate($message, $placeholders);
    }
}