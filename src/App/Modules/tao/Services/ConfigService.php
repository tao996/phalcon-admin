<?php

namespace App\Modules\tao\Services;

use App\Modules\tao\Models\SystemConfig;
use Phax\Foundation\AppService;
use Phax\Support\Exception\LogException;

class ConfigService
{
    const string cacheKey = 'tao_system_config';

    public static function rows(): array
    {
        if (AppService::cache()->has(self::cacheKey)) {
            return (array)(AppService::cache()->get(self::cacheKey)) ?: [];
        }
        return self::forceCache();
    }

    /**
     * 强制缓存配置信息，注意内部是以 gname.name = value 方式保存
     * @return array
     * @throws \Exception
     */
    public static function forceCache(): array
    {
        $data = SystemConfig::queryBuilder()
            ->findColumn('name,gname,value');
        $rows = [];
        foreach ($data as $row) {
            $rows[$row['gname'] . '.' . $row['name']] = $row['value'];
        }
        if (!AppService::cache()->set(self::cacheKey, $rows)) {
            throw new LogException('缓存系统配置信息失败');
        }
        return $rows;
    }

    /**
     * 查询分组配置信息
     * @param string $gname
     * @param bool $resetKey 将 name 重置为 key
     * @return array
     */
    public static function groupRows(string $gname, bool $resetKey = true): array
    {
        $rows = [];
        foreach (self::rows() as $key => $value) {
            if (str_starts_with($key, $gname)) {
                if ($resetKey) {
                    $rows[explode('.', $key)[1]] = $value;
                } else {
                    $rows[$key] = $value;
                }
            }
        }
        return $rows;
    }

    /**
     * 上传配置
     * @param bool $merge 是否合并 `config()->path('tao.upload')` 配置；
     * @return array
     */
    public static function uploadConfig(bool $merge = true): array
    {
        $config = self::groupRows('upload');
        // 如果系统配置文件中存在 config.php
        if ($merge) {
            /*
            $data['tao'] = [
                'upload' => [ // 如果你不想将配置信息写入数据库，则可以写在这里
                    'driver' => 'qnoss', // 优先级高于 系统配置
                    // 七牛云
                    'qnoss_access_key' => '', // 访问密钥 AccessKey
                    'qnoss_secret_key' => '', // 安全密钥 SecretKey
                    'qnoss_bucket' => '', // 存储空间
                    'qnoss_domain' => '', // 访问域名
                    // 阿里云
                    'alioss_access_key_id' => '', // 公钥
                    'alioss_access_key_secret' => '', // 私钥
                    'alioss_endpoint' => '', // 数据中心
                    'alioss_bucket' => '', // 空间名称
                    'alioss_domain' => '', // 访问域名
                    // 腾讯云
                    'txcos_secret_id' => '', // 密钥
                    'txcos_secret_key' => '', // 私钥
                    'txcos_region' => '', // 存储桶地域
                    'txcos_bucket' => '', //

                ],
                // ... 其它配置
            ];
             */
            if ($uploadcc = AppService::config()->getArray('tao.upload')) {
                foreach ($uploadcc as $key => $value) {
                    if (!empty($value)) {
                        $config[$key] = $value;
                    }
                }
            }
        }
        return $config;
    }

    /**
     * 获取配置信息内容
     * @param string $path 由 gname.name 组件
     * @param mixed|string $default 默认值
     * @return mixed|string
     */
    public static function getWith(string $path, mixed $default = ''): mixed
    {
        static $data = null;
        if (is_null($data)) {
            $data = self::rows();
        }
        return $data[$path] ?? $default;
    }


    /**
     * 查询配置分组名称
     * @return array
     */
    public static function findGname(): array
    {
        return SystemConfig::queryBuilder()
            ->distinct('gname')->find();
    }

    /**
     * 配置的值是否为空
     * @param string $value
     * @return bool
     */
    public static function emptyValue(string $value): bool
    {
        return empty($value) || trim($value) == "0";
    }

    public static function notEmptyValue(string $value): bool
    {
        return !self::emptyValue($value);
    }

    /**
     * 是否为启用值，通常为 checkbox
     * @param string $value
     * @return bool
     */
    public static function activeValue(string $value): bool
    {
        return intval($value) == 1;
    }


    public static function compare(string $path, string $output, $cmpValue = "1"): void
    {
        echo self::getWith($path) == $cmpValue ? $output : '';
    }
}