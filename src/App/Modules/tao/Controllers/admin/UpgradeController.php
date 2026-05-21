<?php
/*
* Copyright (c) 2024-present
* Author: tao996<lvshutao@outlook.com>
* 
* For the full copyright and license information, please view the LICENSE.txt
* file that was distributed with this source code.
*/

namespace App\Modules\tao\Controllers\admin;

use App\Modules\tao\BaseController;
use Phax\Utils\MyFileSystem;

class UpgradeController extends BaseController
{
    protected string $htmlTitle = '更新升级';

    /**
     * @return array
     */
    public function indexAction()
    {
        $projects = MyFileSystem::findInDirs(PATH_APP_PROJECTS, 'dir');
        ddd($projects);
        return [];
    }

    /**
     * 更新项目
     */
    public function migrationAction(string $project): bool
    {
        if (!IS_WORKER_WEB) {
            throw new \Exception('migration only run in cli mode');
        }
        $path_project = PATH_APP_PROJECTS . '/' . $project;
        if (!is_dir($path_project)) {
            throw new \Exception('项目不存在');
        }
        $path_project_migrations = $path_project . '/data/migrations';
        if (!is_dir($path_project_migrations)) {
            throw new \Exception('项目没有迁移脚本');
        }
        return true;
    }
}