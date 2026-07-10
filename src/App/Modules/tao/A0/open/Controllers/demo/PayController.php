<?php

namespace App\Modules\tao\A0\open\Controllers\demo;

use App\Modules\tao\A0\open\Models\OpenOrder;
use App\Modules\tao\A0\open\Service\OpenMchService;
use App\Modules\tao\A0\open\Service\OpenUserService;
use App\Modules\tao\BaseController;
use App\Modules\tao\TaoAppService;
use Phax\Foundation\AppService;
use Phax\Support\Exception\BlankException;
use Phax\Support\Exception\BusinessException;
use Phax\Support\Exception\LocationException;

class PayController extends BaseController
{
    protected array|string $openActions = '*';
    public bool $disableUpdateActions = true;

    /**
     * 一个简单的微信支付测试
     * http://localhost:8071/m/tao.open/demo.pay
     */
    public function indexAction()
    {
        $appid = $this->request->getQuery('appid');
        if (empty($appid)) {
            throw new BusinessException('必须指定支付公众号 ID');
        }

        if (TaoAppService::loginAuthHelper()->isLogin()) {
            $user = $this->loginUser();
            if ($openid = OpenUserService::getOpenidByUserId($appid, $user->id)) {
                $redirectURL = TaoAppService::openUrlHelper()->moduleUrl(
                    'tao.wechat/demo.pay/jsapi',
                    ['openid' => $openid, 'appid' => $appid],
                );
                throw new LocationException($redirectURL);
            }
        }
        TaoAppService::wechatHelper()->quickOpenid([
            'appid' => $appid,
            'target' => AppService::urlModule('tao.wechat/demo.pay/jsapi')
        ]);
        throw new BlankException();
    }

    /**
     * jsapi 支付
     * @link http://localhost:8071/m/tao.wechat/demo.pay/jsapi?openid=xxx&appid=xxx
     * @link [JSAPI 调起支付]https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=7_7&index=6
     * @link [JSAPI 下单]https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter3_1_1.shtml
     * @link [获取 openid]https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=4_4
     */
    public function jsapiAction()
    {

        if (!TaoAppService::wechatHelper()->isMicroMessengerBrowser()) {
            throw new BusinessException('只支持在微信浏览器中操作');
        }
        $appid = $this->request->getQuery('appid', 'string');
        $openid = $this->request->getQuery('openid', 'string');

        if ($this->request->isPost()) {
            $data = $this->request->getPost();
            $money = isset($data['money']) ? (float)$data['money'] * 100 : 1; // 默认 1分
            $metadata = ['description' => 'WeTest Pay'];

            $mchid = OpenMchService::getDefaultMchid();

            $prepay = TaoAppService::wepayHelper()->prepay($appid, $mchid);
            $prepay->setOpenid($openid);
            $order = $prepay->createOrder($money, $metadata);
            $order->user_id = 1;
            $order->trade_type = OpenOrder::TradeTypeJsapi;
            return $prepay->prepay($order, $metadata, true);
        }
        return [];
    }

    /**
     * 支付通知
     * @param string $appid 公众号
     * @throws \Exception
     */
    public function notifyAction(string $appid, string $mchid)
    {
        $this->autoResponse = false;
        $payNotifyHelper = TaoAppService::wepayHelper()->notify($appid, $mchid);
        return $payNotifyHelper->response();
    }

    /**
     * 退款通知
     * @param string $outTradeNo 订单号
     * @throws \Exception
     */
    public function refundNotifyAction(string $outTradeNo)
    {
        $this->autoResponse = false;
        $refundNotifyHelper = TaoAppService::wepayHelper()->refundNotify($outTradeNo);
        return $refundNotifyHelper->response();
    }

}