<?php

namespace App\Modules\tao\A0\open\Helper;


use App\Modules\tao\sdk\SdkHelper;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Phax\Support\Exception\BlankException;

class WechatHelper
{
    public function __construct(private MyOpenMvcHelper $helper)
    {
    }

    /**
     * 是否微信浏览器
     * @return bool
     */
    public function isMicroMessengerBrowser(): bool
    {
        $ua = $this->helper->mvc->request()->getUserAgent();
        return str_contains($ua, 'MicroMessenger');
    }

    /**
     * 直接发送响应信息给微信服务器
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return \Phalcon\Http\ResponseInterface
     */
    public function response(\Psr\Http\Message\ResponseInterface $response)
    {
        return $this->helper->mvc->responseHelper()->send(
            $response->getBody()->getContents(),
            $response->getStatusCode()
        );
    }

    /**
     * 直接输出一个二维码
     * @param string $data 二维码数据
     */
    public function renderQrcode(string $data): string
    {
        SdkHelper::qrcode();

        $qrCode = QrCode::create($data)
            ->setSize(300)->setMargin(10)
            ->setForegroundColor(new Color(0, 0, 0))
            ->setBackgroundColor(new Color(255, 255, 255));

        $writer = new PngWriter();
        $result = $writer->write($qrCode);
        header('Content-Type: ' . $result->getMimeType());
        echo $result->getString();
        throw new BlankException();
    }

    /**
     * 跳转到微信简易授权，主要用于获取用户的 openid
     * @param array $query 查询参数，其中 appid 和 target（内部地址） 是必须的
     * @param bool $jump 是否自动跳转
     * @param bool $qrcode 如果不是微信浏览器，则显示二维码供扫描
     * @return string
     * @throws BlankException
     */
    public function quickOpenid(array $query = [], bool $jump = true, bool $qrcode = true): string
    {
        if (empty($query['appid'])) {
            throw new \Exception('appid should not empty');
        }
        if (empty($query['target'])) {
            throw new \Exception('target should not empty');
        }
        $redirectURL = $this->helper->openUrlHelper()->moduleUrl('tao.wechat/auth', $query);
        if ($qrcode && !self::isMicroMessengerBrowser()) {
            self::renderQrcode($redirectURL);
        }
        if ($jump) {
            header("Location:{$redirectURL}");
            throw new BlankException();
        }
        return $redirectURL;
    }
}