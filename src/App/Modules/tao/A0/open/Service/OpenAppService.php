<?php

namespace App\Modules\tao\A0\open\Service;

use App\Modules\tao\A0\open\Helper\Libs\CertSecretHelper;
use App\Modules\tao\A0\open\Helper\Libs\PayCertHelper;
use App\Modules\tao\A0\open\Models\OpenApp;
use App\Modules\tao\Data\UserBindPlatform;
use Phax\Foundation\AppService;
use Phax\Support\Exception\BusinessException;
use Phax\Support\Exception\LogException;
use Phax\Utils\MyData;

class OpenAppService
{
    const string cacheKey = 'tao_open_app';

    /**
     * 获取所有应用配置信息
     */
    public static function records(): array
    {
        $cache = AppService::cache();
        if ($cache->has(self::cacheKey)) {
            return (array)$cache->get(self::cacheKey);
        }
        return self::findCache();
    }

    /**
     * 强制从数据库中查询记录，然后更新缓存
     * @return array
     * @throws LogException
     */
    public static function findCache(): array
    {
        $rows = OpenApp::queryBuilder()
            ->int('status', 1)
            ->findColumn(key: 'appid');
        if (!AppService::cache()->set(self::cacheKey, $rows)) {
            throw new LogException('更新应用缓存失败');
        }
        return $rows;
    }

    /**
     * @param string $appid
     * @param bool $must
     * @return array|null
     * @throws BusinessException
     */
    public static function getWithAppid(string $appid, bool $must = true): ?array
    {
        $data = self::records();
        if ($must && !isset($data[$appid])) {
            throw new BusinessException('没有找到(' . $appid . ')的应用配置');
        }
        return (array)$data[$appid] ?? null;
    }


    /**
     * @throws \Exception
     */
    public static function kindCompare(string $appid, string $kind): bool
    {
        $wc = self::getWithAppid($appid);
        switch ($kind) {
            case 'mini':
                return self::isMini($wc['kind']);
            case 'gzh':
                return self::isGzh($wc['kind']);
            case 'dyh':
                return $wc['kind'] == 'dyh';
            case 'fwh':
                return $wc['kind'] == 'fwh';
            case 'web':
                return self::isWeb($wc['kind']);
            case 'work':
                return self::isWork($wc['kind']);
            default:
                throw new BusinessException('kind value is not allow:' . $kind);
        }
    }


    public static function isMini($kind): bool
    {
        return $kind == 'mini';
    }

    /**
     * 是否公众号
     * @return bool
     */
    public static function isGzh($kind): bool
    {
        return in_array($kind, ['dyh', 'fwh', 'gzh']);
    }

    /**
     * 是否网页应用
     * @return bool
     */
    public static function isWeb($kind): bool
    {
        return $kind == 'web';
    }

    /**
     * 是否企业微信
     * @return bool
     */
    public static function isWork($kind): bool
    {
        return $kind == 'work';
    }

    /**
     * 创建用户账号时的绑定类型
     * @param array $app
     * @return string
     * @throws \Exception
     */
    public static function newUserBind(array $app): string
    {
        MyData::mustHasSet($app, ['platform', 'kind']);
        $bind = '';
        switch ($app['platform']) {
            case UserBindPlatform::PlatformWechat:
                $bind = 'wechat';
                break;
            case UserBindPlatform::PlatformTiktok:
                $bind = 'tiktok';
                break;
        }
        return $bind . ucfirst($app['kind']);
    }


    public static function getPIndex(string $name): string
    {
        static $dd = [
            'public_key' => 'pi0',
            'rsa_public_key' => 'pi1',
            'rsa_private_key' => 'pi2'
        ];
        if (!isset($dd[$name])) {
            throw new BusinessException('不存在的证书字段:' . $name);
        }
        return $dd[$name];
    }

    /**
     * 加密字段，并保存模型数据
     * @param OpenApp $app
     * @param string $name public_key|rsa_public_key|rsa_private_key
     * @param string $content 证书内容
     * @return boolean
     */
    public static function encrypt(OpenApp $app, string $name, string $content): bool
    {
        $pIndexName = self::getPIndex($name);
        if (strlen($content) < 100) {
            throw new BusinessException('证书内容过短或不符合规范？');
        }
        $fMd5 = md5($content);
        $pIndex = rand(30, 80);
        $newContent = CertSecretHelper::encryptData($content, $pIndex, 5);

        $app->assign([
            $name => $fMd5,
            $pIndexName => $pIndex,
        ]);
        if ($app->save()) {
            $dir = PayCertHelper::dir();
            if (!file_put_contents($dir . $fMd5, $newContent)) {
                throw new LogException('app 保存证书失败', ['file' => $dir . $fMd5]);
            }
            return true;
        }
        return false;
    }

    /**
     * 还原证书内容（交易系统专用）
     * @param string $filename 文件名称，来自 TiktokApp 中的 public_key|rsa_public_key|rsa_private_key 内容
     * @param int $pIndex 来自 TiktokApp 中的 pi0|pi1|pi2
     * @return string 解密内容
     */
    public static function decrypt(string $filename, int $pIndex): string
    {
        if (empty($filename)) {
            throw new BusinessException('tiktok 证书文件名不能为空');
        } elseif ($pIndex < 1) {
            throw new BusinessException('tiktok 证书加密索引不能为空');
        }
        $file = PayCertHelper::dir() . $filename;
        if (!file_exists($file)) {
            throw new BusinessException('tiktok 证书不存在');
        }
        $content = file_get_contents($file);
        return CertSecretHelper::decryptData($content, $pIndex, 5);
    }
}