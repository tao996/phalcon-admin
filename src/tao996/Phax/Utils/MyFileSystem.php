<?php

namespace Phax\Utils;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class MyFileSystem
{
    /**
     * 跳过 . 和 .. 文件名
     * @param $name
     * @return bool
     */
    public static function excludeFileNames($name): bool
    {
        return in_array($name, ['.', '..']);
    }

    /**
     * 获取指定目录下的文件和目录的名称
     * @param string $parentDir
     * @param string $type dir 目录；file 文件；默认为空，表示文件和目录
     * @return array ['a.php', 'someDir']
     */
    public static function findInDirs(string $parentDir, string $type = ''): array
    {
        $rows = [];
        if (is_dir($parentDir)) {
            foreach (scandir($parentDir) as $name) {
                if (!self::excludeFileNames($name)) {
                    if ('' === $type
                        || ('dir' === $type && is_dir($parentDir . '/' . $name))
                        || ('file' === $type && is_file($parentDir . '/' . $name))
                    ) {
                        $rows[] = $name;
                    }
                }
            }
        }
        return $rows;
    }

    /**
     * 根据 $filter 过滤目录下的文件
     * @param string $directory 目录
     * @param callable{string}|null $filter 过滤器
     * @return array
     */
    public static function getFilesInDirectory(string $directory, callable $filter = null): array
    {
        $files = [];
        if (is_dir($directory)) {
            $dirIterator = new \RecursiveDirectoryIterator($directory);
            $iterator = new \RecursiveIteratorIterator($dirIterator);
            $hasFilter = $filter !== null;
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $path = str_replace('\\', '/', $file->getPathname());
                    if ($hasFilter) {
                        if (!$filter($path)) {
                            $files[] = $path;
                        }
                    } else {
                        $files[] = $path;
                    }
                }
            }
        }
        return $files;
    }

    /**
     * 拼接文件路径，注意，不处理 '\' 符号
     * @param $directory
     * @param $filename
     * @return string
     */
    public static function fullpath($directory, $filename): string
    {
        return rtrim($directory, '/') . '/' . ltrim($filename, '/');
    }

    public static function readWithLines(string $filePath, callable $callback): void
    {
        if (!file_exists($filePath)) {
            throw new \Exception("文件不存在: " . $filePath);
        }

        $fileHandle = fopen($filePath, 'r');
        if ($fileHandle) {
            while (($line = fgets($fileHandle)) !== false) {
                $line = rtrim($line);
                call_user_func($callback, $line);
            }
            fclose($fileHandle);
        } else {
            throw new \Exception("无法打开文件: " . $filePath);
        }
    }

    /**
     * 根据 $gitignoreContent 生成过滤的内容
     * @param string $gitignoreContent .gitignore 的仙鹤
     * @return array
     */
    public static function generateFilterPatternsByGitignore(string $gitignoreContent): array
    {
        $includes = []; // 必须包含的文件，通常用于 !开头
        $excludes = [];

        $patterns = explode("\n", $gitignoreContent);
        $patterns = array_filter($patterns); // 去除空行

        foreach ($patterns as $pattern) {
            $save_include = true;
            if (str_starts_with($pattern, '!')) {
                $pattern = ltrim($pattern, '!');
                $save_include = false;
            }

            if (str_ends_with($pattern, '/')) {
                $pattern = rtrim($pattern, '/') . '/*';
            } elseif (preg_match('|\w$|', $pattern)) {
                $pattern .= '*';
            }
            $pattern = str_replace('.', '\.', $pattern);
            $pattern = str_replace('*', '.*', $pattern);
            $pattern = str_replace('?', '.', $pattern);
            $pattern = '/^' . str_replace('/', '\/', $pattern) . '$/';
            if ($save_include) {
                $excludes[] = $pattern;
            } else {
                $includes[] = $pattern;
            }
        }
        return [$includes, $excludes];
    }

    /**
     * 使用 createFilterByGitignore 生成的规则来过滤文件
     * @param string $file 文件路径
     * @param array $patterns createFilterByGitignore 生成的规则
     * @param string $prefix_dir 文件所在的目录
     * @return bool 如果被过滤掉则返回 true
     */
    public static function filterByGitignorePatterns(string $file, array $patterns, string $prefix_dir = ''): bool
    {
        if ($prefix_dir) {
            if (!str_ends_with($prefix_dir, '/')) {
                $prefix_dir .= '/';
            }
            $file = str_replace($prefix_dir, '', $file);
        }
        foreach ($patterns[0] as $include_pattern) {
            if (preg_match($include_pattern, $file)) {
                return false;
            }
        }
        foreach ($patterns[1] as $exclude_pattern) {
            if (preg_match($exclude_pattern, $file)) {
                return true;
            }
        }

        return false;
    }
}