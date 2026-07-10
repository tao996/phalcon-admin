<?php

namespace App\Modules\tao\A0\open\Helper;

use App\Modules\tao\A0\open\Service\OpenAppService;
use App\Modules\tao\A0\open\Service\OpenMchService;
use App\Modules\tao\sdk\SdkHelper;
use App\Modules\tao\TaoAppService;
use Phax\Support\Exception\BusinessException;
use Phax\Support\Exception\LogException;
use Phax\Utils\MyAssert;

use App\Modules\tao\A0\open\Helper\Libs\PayCertHelper;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use EasyWeChat\OfficialAccount\Application as OfficialApplication;
use EasyWeChat\Pay\Application as PayApplication;
use EasyTiktok\MiniApp\Application as TikTokApplication;
use EasyWeChat\MiniApp\Application as MiniApplication;


readonly class ApplicationHelper
{

    private function tiktokSDK(): void
    {
        static $unload = true;
        if ($unload) {
            $unload = false;
            loader()
                ->addNamespace('EasyTiktok', SdkHelper::PATH, 'easytiktok/src')
                ->register();
        }
    }

    /**
     * 抖音小程序实例
     */
    public function getTiktok(array $app): TikTokApplication
    {
        $this->tiktokSDK();
        MyAssert::mustHasSet($app, ['appid', 'secret', 'sandbox', 'kind']);

        if (!OpenAppService::isMini($app['kind'])) {
            throw new BusinessException('tiktok mini appid is invalid');
        }

        try {
            $app = new TikTokApplication([
                'app_id' => $app['appid'],
                'secret' => $app['secret'],
                'sandbox' => $app['sandbox'],
                'http' => ['throw' => true]
            ]);
            $app->setCache(TaoAppService::redisCache());
            return $app;
        } catch (\Exception $e) {
            throw new LogException('Tiktok 小程序配置失败', ['app'=>$app],previous: $e);
        }
    }

    /**
     * 微信公众号实例
     * @param $appid string 微信 appID
     * @return OfficialApplication
     */
    public function getOfficial(string $appid): OfficialApplication
    {
        if (empty($appid)) {
            throw new BusinessException('wechat official appid is empty');
        }

        $wa = OpenAppService::getWithAppid($appid);
        if (!OpenAppService::isGzh($wa['kind'])) {
            throw new BusinessException('不是公众号 appid');
        }
        try {
            $app = new OfficialApplication(
                [
                    'app_id' => $wa['appid'],
                    'token' => $wa['token'],
                    'secret' => $wa['secret'],
                    'aes_key' => $wa['aes_key'],
                    'http' => [
                        'throw' => false,
                    ]
                ]
            );
            $app->setCache(TaoAppService::redisCache());
            return $app;
        } catch (\Exception $e) {
            throw new LogException('微信公众号配置失败', [
                'app' => $wa
            ], previous: $e);
        }
    }

    /**
     * 微信支付实例
     * @throws InvalidArgumentException
     */
    public function getPay(string $appid, string $mchid): PayApplication
    {
        if (empty($appid)) {
            throw new BusinessException('必须指定微信应用 appid');
        }
        if (empty($mchid)) {
            throw new BusinessException('必须指定微信支付商户号');
        }
        // $app = OpenAppService::getWithAppid($appid); // 应用配置信息
        $mch = OpenMchService::getWithMchid($mchid);
        $certDir = PayCertHelper::dir();
        $config = [
            'app_id' => $appid,
            'mch_id' => $mch['mchid'],
            // 商户证书
            'private_key' => $certDir . $mch['private_key'], //client_key.pem
            'certificate' => $certDir . $mch['certificate'], //client_cert.pem
            // v3 API 秘钥
            'secret_key' => $mch['secret_key'],
            // v2 API 秘钥
//            'v2_secret_key' =>'',
            // 平台证书：微信支付 APIv3 平台证书，需要使用工具下载
            // 下载工具：https://github.com/wechatpay-apiv3/CertificateDownloader
            'platform_certs' => [ // 请使用绝对路径
                $certDir . $mch['platform_cert'],
//                $mch['pubkey_id'] => $certDir . $mch['pubkey']
            ],
            /**
             * 接口请求相关配置，超时时间等，具体可用参数请参考：
             * https://github.com/symfony/symfony/blob/5.3/src/Symfony/Contracts/HttpClient/HttpClientInterface.php
             */
            'http' => [
                'throw' => true, // 状态码非 200、300 时是否抛出异常，默认为开启
                'timeout' => 5.0,
                // 'base_uri' => 'https://api.mch.weixin.qq.com/', // 如果你在国外想要覆盖默认的 url 的时候才使用，根据不同的模块配置不同的 uri
            ],
        ];

        // https://easywechat.com/6.x/pay/
        return new PayApplication($config);
    }

    /**
     * 获取微信小程序
     * @param array|string $app 小程序配置，或者小程序 appid
     * @return MiniApplication
     */
    public function getMini(array|string $app): MiniApplication
    {
        if (is_string($app)) {
            $app = OpenAppService::getWithAppid($app);
        }
        MyAssert::mustHasSet($app, ['appid', 'secret', 'kind'], ['token', 'aes_key']);


        if (!OpenAppService::isMini($app['kind'])) {
            throw new BusinessException('不是小程序 appid');
        }
        try {
            $app = new MiniApplication([
                'app_id' => $app['appid'],
                'secret' => $app['secret'],
                'token' => $app['token'],
                'aes_key' => $app['aes_key'],
                'http' => [
                    'throw' => false
                ]
            ]);
            $app->setCache(TaoAppService::redisCache());
            return $app;
        } catch (\Exception $e) {
            throw new LogException('微信小程序配置失败', [
                'app' => $app,
            ], previous: $e);
        }
    }
}