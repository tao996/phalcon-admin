<?php

namespace App\Modules\tao\A0\open\Service;

use App\Modules\tao\A0\open\Helper\MyOpenMvcHelper;
use App\Modules\tao\A0\open\Models\OpenConfig;
use Phalcon\Cache\Exception\InvalidArgumentException;
use Phax\Support\Logger;


class OpenConfigService
{
    private const string cacheKey = 'tao_open_config';
    private \Phalcon\Cache\Cache $cache;

    public function __construct(private readonly MyOpenMvcHelper $helper)
    {
        $this->cache = $this->helper->mvc->cache();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function rows(): array
    {
        if ($this->cache->has(self::cacheKey)) {
            return (array)$this->cache->get(self::cacheKey);
        }
        return self::cache();
    }

    /**
     * 强制缓存配置信息
     * @return array
     * @throws \Exception
     */
    public function cache(): array
    {
        $data = OpenConfig::queryBuilder()->findColumn('name,value');
        $rows = array_column($data, 'value', 'name');
        if (!$this->cache->set(self::cacheKey, $rows)) {
            Logger::error('cache open.config failed:' . __CLASS__);
        }
        return $rows;
    }

    public function getWith(string $name, int|string $default = '')
    {
        $data = $this->rows();

        if (!empty($data[$name])) {
            if (trim($data[$name]) == "0"){
                return $default;
            }
            return $data[$name];
        }
        return $default;
    }


    public function updateValue(OpenConfig $model, $name, $value): bool
    {
        $sql = 'UPDATE ' . $model->getSource() . ' SET value=? WHERE name=?';
        return $model->getDI()->get('db')->execute(
            $sql,
            [$value, $name],
            [\PDO::PARAM_STR, \PDO::PARAM_STR]
        );
    }
}