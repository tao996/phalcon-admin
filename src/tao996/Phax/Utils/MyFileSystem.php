<?php

namespace Phax\Utils;

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

    public static function getFilesInDirectory($directory, callable $filter = null): array
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
}