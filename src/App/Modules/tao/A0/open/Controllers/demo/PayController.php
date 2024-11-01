<?php

namespace App\Modules\tao\A0\open\Controllers\demo;


use App\Modules\tao\A0\open\BaseOpenController;
use App\Modules\tao\A0\open\Models\OpenOrder;
use Phax\Support\Exception\BlankException;

class PayController extends BaseOpenController
{
    protected array|string $openActions = '*';
    public bool $disableUpdateActions = true;

    protected function localInitialize(): void
    {
    }

    /**
     * 一个简单的微信支付测试
     * http://localhost:8071/m/tao.open/demo.pay
     * @throws \Exception
     */
    public function indexAction()
    {
        $appid = $this->request->getQuery('appid');
        if (empty($appid)) {
            throw new \Exception('必须指定支付公众号 ID');
        }

        if ($this->vv->loginAuthHelper()->isLogin()) {
            $user = $this->loginUser();
            if ($openid = $this->mvc->userService()->getOpenidByUserId($appid, $user->id)) {
                $redirectURL = $this->mvc->openUrlHelper()->moduleUrl(
                    'tao.wechat/demo.pay/jsapi',
                    ['openid' => $openid, 'appid' => $appid],
                );
                header("Location:{$redirectURL}");
                throw new BlankException();
            }
        }
        $this->mvc->wechatHelper()->quickOpenid([
            'appid' => $appid,
            'target' => $this->vv->urlModule('tao.wechat/demo.pay/jsapi')
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

        if (!$this->mvc->wechatHelper()->isMicroMessengerBrowser()) {
            throw new \Exception('只支持在微信浏览器中操作');
        }
        $appid = $this->request->getQuery('appid', 'string');
        $openid = $this->request->getQuery('openid', 'string');

        if ($this->request->isPost()) {
            $data = $this->request->getPost();
            $money = isset($data['money']) ? (float)$data['money'] * 100 : 1; // 默认 1分
            $metadata = ['description' => 'WeTest Pay'];

            $mchid = $this->mvc->mchService()->getMchid();

            $prepay = $this->mvc->wepayHelper()->prepay($appid, $mchid);
            $prepay->setOpenid($openid);
            $order = $prepay->createOrder($money, $metadata);
            $order->user_id = 1; // $this->loginUser->userId();
            $order->trade_type = OpenOrder::TradeTypeJsapi;
            return $prepay->prepay($order, $metadata);
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
        $payNotifyHelper = $this->mvc->wepayHelper()->notify($appid, $mchid);
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
        $refundNotifyHelper = $this->mvc->wepayHelper()->refundNotify($outTradeNo);
        return $refundNotifyHelper->response();
    }

}