<?php

namespace App\Modules\tao\Services;

use App\Modules\tao\Helper\MyMvcHelper;
use App\Modules\tao\Models\SystemMigration;
use Phax\Db\Transaction;
use Phax\Support\Logger;

class MigrationService
{
    public function __construct(public MyMvcHelper $mvc)
    {
    }

    /**
     * 执行版本更新 <br>
     * 某些 DDL 语句会自动触发事务，所以尽量不要在语句中包含 ALTER TABLE...
     * 如果待导入的文件非常大，则需要设置内存: ini_set('memory_limit', '5120M');set_time_limit ( 0 );
     * @link https://stackoverflow.com/questions/147821/loading-sql-files-from-within-php
     * @param string $version 版本号，必须唯一
     * @param string $summary 更新说明
     * @param callable(\PDO):void $handle 待处理函数；
     * @return bool 是否执行成功
     * @throws \Exception
     */
    public function upgrade(string $version, string $summary, callable $handle): bool
    {
        if (empty($version)) {
            throw new \Exception('必须指定 version');
        }
        $tableName = SystemMigration::getObject()->getSource();
        if (!self::versionExits($version)) {
            $params = [
                time(),
                $version,
                $summary
            ];

            Transaction::pdo($this->mvc->pdo(), function (\PDO $db) use ($tableName, $params, $handle) {
                $stmt = $db->prepare('INSERT INTO ' . $tableName . ' (created_at,version,summary) values(?,?,?)');
                if (!$stmt->execute($params)) {
                    Logger::message('insert migration record failed', $db->errorInfo());
                }
                if ($db->lastInsertId() < 1) {
                    throw new \Exception('could not get the lastInsertId when insert migrate record');
                }
                $handle($db);
            });
            return true;
        } else {
            return false;
        }
    }

    /**
     * 版本号是否存在
     * @throws \Exception
     */
    public function versionExits($version): bool
    {
        return SystemMigration::queryBuilder()
            ->string('version', $version)
            ->exits();
    }
}