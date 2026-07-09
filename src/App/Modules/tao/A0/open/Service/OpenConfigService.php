<?php

namespace App\Modules\tao\A0\open\Service;

use App\Modules\tao\A0\open\Models\OpenConfig;
use Phax\Foundation\AppService;
use Phax\Support\Exception\LogException;


class OpenConfigService
{
    const string cacheKey = 'tao_open_config';

    public static function records(): array
    {
        $cache = AppService::cache();
        if ($cache->has(self::cacheKey)) {
            return (array)$cache->get(self::cacheKey);
        }
        return self::findCache();
    }

    /**
     * 强制缓存配置信息
     * @return array
     */
    public static function findCache(): array
    {
        $data = OpenConfig::queryBuilder()
            ->findColumn('name,value');
        $rows = array_column($data, 'value', 'name');
        if (!AppService::cache()->set(self::cacheKey, $rows)) {
            throw new LogException('更新 open 模块缓存失败');
        }
        return $rows;
    }

    public static function getWith(string $name, int|string $default = '')
    {
        $data = self::records();
        if (!empty($data[$name])) {
            if (trim($data[$name]) == "0") {
                return $default;
            }
            return $data[$name];
        }
        return $default;
    }


    public static function updateValue(OpenConfig $model, $name, $value): bool
    {
        $sql = 'UPDATE ' . $model->getSource() . ' SET value=? WHERE name=?';
        return $model->getDI()->get('db')->execute(
            $sql,
            [$value, $name],
            [\PDO::PARAM_STR, \PDO::PARAM_STR]
        );
    }
}