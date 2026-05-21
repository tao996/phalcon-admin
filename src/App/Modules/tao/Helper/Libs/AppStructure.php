<?php

namespace App\Modules\tao\Helper\Libs;

use Phax\Utils\MyFileSystem;

class AppStructure
{


    /**
     * 获取全部模块下的控制器文件
     * @throws \Exception
     */
    public static function scanModules(array $skipModules = []): array
    {
        $rows = [];
        foreach (scandir(PATH_APP_MODULES) as $name) {
            if (in_array($name, $skipModules)) {
                continue;
            }
            if (!MyFileSystem::excludeFileNames($name) && is_dir(PATH_APP_MODULES . $name)) {
                $rows[$name] = self::findInModule($name);
            }
        }
        return $rows;
    }

    public static function scanProject(string $project): array
    {
        return self::getControllerFiles($project);
    }


    /**
     * 扫描模块下全部控制器文件
     * @param string $module 模块名称
     * @return array 控制器文件列表
     * @throws \Exception
     */
    public static function findInModule(string $module): array
    {
        $pathModule = PATH_APP_MODULES . $module;
        return self::getControllerFilesInDeep($pathModule);
    }

    public static function findInProject(string $project): array
    {
        $pathProject = PATH_APP_PROJECTS . $project;
        return self::getControllerFiles($pathProject);
    }

    /**
     * 获取全部的控制器（含子模块 A0）
     * @param string $dir 通常为模块或项目目录
     * @return array
     * @throws \Exception
     */
    public static function getControllerFilesInDeep(string $dir): array
    {
        if (!is_dir($dir)) {
            throw new \Exception('不是一个有效的目录');
        }
        $dir = rtrim($dir, '/');

        $rows = self::getControllerFiles($dir . '/Controllers');
        $rows['a0'] = [];
        // 扩展模块
        $a0s = MyFileSystem::findInDirs($dir . '/A0', 'dir');

        foreach ($a0s as $a0) {
            $a0Controllers = $dir . '/A0/' . $a0 . '/Controllers';
            $rows['a0'][$a0] = self::getControllerFiles($a0Controllers);
        }
        return $rows;
    }


    /**
     * 扫描控制器目录（含子目录），并返回控制器文件名列表
     * @param string $pathControllers 必须为 /xxx/Controllers
     * @return array 文件列表
     */
    public static function getControllerFiles(string $pathControllers): array
    {
        $rows = [
            'controllers' => [],
            'subDirs' => []
        ];
        if (is_dir($pathControllers)) {
            foreach (scandir($pathControllers) as $ctrl) {
                if (!MyFileSystem::excludeFileNames($ctrl)) {
                    if (str_ends_with($ctrl, 'Controller.php')) {
                        $rows['controllers'][] = $ctrl;
                    } elseif (is_dir($pathControllers . '/' . $ctrl)) { // 子目录
                        $rows['subDirs'][$ctrl] = [];
                        foreach (scandir($pathControllers . '/' . $ctrl) as $subCtrl) {
                            if (str_ends_with($subCtrl, 'Controller.php')) {
                                $rows['subDirs'][$ctrl][] = $subCtrl;
                            }
                        }
                    }
                }
            }
        }
        return $rows;
    }


}