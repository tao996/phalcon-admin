<?php

namespace App\Modules\tao\A0\open\Service;

use App\Modules\tao\A0\open\Models\OpenMch;
use Phax\Foundation\AppService;
use Phax\Support\Exception\BusinessException;
use Phax\Support\Exception\LogException;

/**
 * 支付商户
 */
class OpenMchService
{
    const string cacheKey = 'tao.pay.mch';

    public static function records(): array
    {
        static $cache = null; // decode 后的数据
        if (!is_null($cache)) {
            return $cache;
        }
        if (AppService::cache()->has(self::cacheKey)) {
            $cache = json_decode(AppService::cache()->get(self::cacheKey), true);
            return $cache;
        }

        return self::findCache();
    }

    /**
     * 强制缓存商户列表
     * @return array{id:int,mchid:string,private_key:string,certificate:string,secret_key:string,v2_secret_key:string,platform_cert:string}
     */
    public static function findCache(): array
    {
        if ($cache = OpenMch::queryBuilder()
            ->where('done', 1)
            ->findColumn([
                'id',
                'mchid',
                'private_key',
                'certificate',
                'secret_key',
                'v2_secret_key',
                'platform_cert'
            ], key: 'mchid')) {
            if (!AppService::cache()->set(self::cacheKey, json_encode($cache))) {
                throw new LogException('更新商户列表缓存失败');
            }
            return $cache;
        }
        return [];
    }

    /**
     * 获取指定商户配置信息
     * @param string $mchid 商户号
     * @return array{id:int, mchid:string, private_key:string, certificate:string, secret_key:string, v2_secret_key:string, platform_cert:string}
     */
    public static function getWithMchid(string $mchid): array
    {
        if (empty($mchid)) {
            throw new BusinessException('商户号不能为空');
        }
        if ($rows = self::records()) {
            if (isset($rows[$mchid])) {
                return $rows[$mchid];
            }
        }

        if ($rows = self::findCache()) {
            if (isset($rows[$mchid])) {
                return $rows[$mchid];
            }
        }

        throw new BusinessException('找不到指定商户号' . $mchid);
    }

    /**
     * 获取默认支付商户号
     * @return string
     */
    public static function getDefaultMchid(): string
    {
        return OpenConfigService::getWith('pay_mchid', '');
    }

}