<?php

namespace App\Modules\tao\A0\open\Controllers\weixin;

use App\Modules\tao\A0\open\BaseOpenDeveloperController;
use Phax\Support\Exception\BlankException;

use Phax\Support\Logger;
use Phax\Utils\MyData;

class MiniController extends BaseOpenDeveloperController
{
    protected array|string $openActions = '*';

    /**
     * 获取授权用户的 session_key 和 openid
     * @link https://demo.fushuilu.com/api/m/tao.open/weixin.mini/code2session
     * @method POST
     * @query {appid:小程序appid}
     * @body {code:login接口返回的登录凭证,userInfo:{avatarUrl:头像,nickName:昵称}} 其它参数如 encryptedData,iv,rawData,signature 不是必须的
     * @throws \Exception
     */
    public function code2SessionAction()
    {
        $this->mustPostMethod();

        // 用户传上来的资料信息
        $this->vv->validate()->check($this->requestData, ['code' => 'required']);
        $code = $this->requestData['code'];
        if (empty($code)) {
            throw new \Exception('code 参数不能为空');
        }
        $appid = $this->getAppid();
        $app = $this->mvc->appService()->getWithAppid($appid); // 应用配置信息
        $data = $this->mvc->miniAppHelper()->code2Session($app, $code); // session_key, openid, unionid
        $baseInfo = $this->mvc->userService()->save($app, $data, $this->requestData['userInfo'] ?? []);
        Logger::debug($data, $baseInfo);
        // token-secret
        $baseInfo['ts'] = $this->tryGetLoginAuth()->getAdapter()->saveUser(['id' => $baseInfo['user_id']]);
        return $baseInfo; // [id, user_id, nickname,avatar_url, openid, ts]
    }
    /**
     * 获取不限制的小程序码
     * https://developers.weixin.qq.com/miniprogram/dev/OpenApiDoc/qrcode-link/qr-code/getUnlimitedQRCode.html
     * @return void
     * @throws \Exception
     */
    public function qRCodeAction()
    {
        $appid = $this->getAppid();
        $this->vv->validate()->check($this->requestData, [
            'scene|场景值' => 'required|strlenmax:32'
        ]);
        /*
        {
            "scene":"test",
            "page":"pages/index/index",
            "env_version":"trial"
        }
         */
        $data = MyData::getByKeys($this->requestData, [
            'scene', // key=value
            'page',
            'check_path', // bool
            'env_version', // release|trial|develop
            'width',
            'auto_color',
            'line_color'
        ]);
        $app = $this->mvc->application()->getMini($appid);
        $response = $app->getClient()->postJson('/wxa/getwxacodeunlimit', $data);
        header("Content-Type: image/jpeg");
        echo $response->getContent();
        throw new BlankException();
    }
}