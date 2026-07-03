<?php

namespace App\Modules\tao\A0\open\Service;


use App\Modules\tao\A0\open\Helper\MyOpenMvcHelper;
use App\Modules\tao\A0\open\Models\OpenMch;
use Phax\Support\Exception\BusinessException;
use Phax\Support\Exception\LogException;

/**
 * 支付商户
 */
class OpenMchService
{
    protected const string cacheKey = 'tao.pay.mch';
    private \Phalcon\Cache\Cache $cache;

    public function __construct(private readonly MyOpenMvcHelper $helper)
    {
        $this->cache = $this->helper->mvc->cache();
    }

    private function rows(): array
    {
        static $cache = null;
        if (!is_null($cache)) {
            return $cache;
        }
        if ($this->cache->has(self::cacheKey)) {
            $cache = json_decode($this->cache->get(self::cacheKey), true);
            return $cache;
        }

        return self::cache();
    }

    /**
     * 强制缓存商户列表
     * @return array{id:int,mchid:string,private_key:string,certificate:string,secret_key:string,v2_secret_key:string,platform_cert:string}
     */
    public function cache(): array
    {
        if ($cache = OpenMch::queryBuilder($this->helper->mvc->getDi())
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
            if (!$this->cache->set(self::cacheKey, json_encode($cache))) {
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
    public function getWith(string $mchid): array
    {
        if (empty($mchid)) {
            throw new BusinessException('商户号不能为空');
        }
        if ($rows = self::rows()) {
            if (isset($rows[$mchid])) {
                return $rows[$mchid];
            }
        }

        if ($rows = self::cache()) {
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
    public function getMchid(): string
    {
        return $this->helper->configService()->getWith('pay_mchid', '');
    }

}