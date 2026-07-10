<?php

namespace App\Modules\tao\A0\open\Controllers\weixin;

use App\Modules\tao\A0\open\BaseOpenMiniController;
use App\Modules\tao\A0\open\Service\OpenAppService;
use App\Modules\tao\A0\open\Service\OpenUserService;
use App\Modules\tao\Models\SystemUser;

use App\Modules\tao\TaoAppService;
use Phax\Foundation\AppService;
use Phax\Support\Exception\BusinessException;
use Phax\Support\Logger;
use Phax\Support\Validate;
use Phax\Utils\MyData;

class MiniController extends BaseOpenMiniController
{
    protected array|string $openActions = '*';

    /**
     * 获取授权用户的 session_key 和 openid
     * @link https://demo.fushuilu.com/api/m/tao.open/weixin.mini/code2session
     * @method POST
     * @query {appid:小程序appid}
     * @body {code:login接口返回的登录凭证,userInfo:{avatarUrl:头像,nickName:昵称}} 其它参数如 encryptedData,iv,rawData,signature 不是必须的
     */
    public function code2SessionAction()
    {
        $this->mustPostMethod();

        // 用户传上来的资料信息
        Validate::checkData($this->requestData, ['code' => 'required']);
        $code = $this->requestData['code'];
        if (empty($code)) {
            throw new BusinessException('code 参数不能为空');
        }
        $appid = $this->getAppid();
        $app = OpenAppService::getWithAppid($appid); // 应用配置信息
        $data = TaoAppService::miniAppHelper()->code2Session($app, $code); // session_key, openid, unionid
        $baseInfo = OpenUserService::save($app, $data, $this->requestData['userInfo'] ?? []);
        if (IS_DEBUG) {
            Logger::debug('code2SessionAction', $data, $baseInfo);
        }

        // token-secret
        $user = new SystemUser();
        $user->id = $baseInfo['user_id'];
        $baseInfo['ts'] = $this->tryGetLoginAuth()->getAdapter()->saveUser($user, [
            'EX' => 604800, // 24*3600*7 = 7 天
        ]);
        $baseInfo['expired_at'] = time() + 604800 - 60;  // 过期时间
        return $baseInfo; // [id, user_id, nickname,avatar_url, openid, ts]
    }

    /**
     * 获取不限制的小程序码
     * https://developers.weixin.qq.com/miniprogram/dev/OpenApiDoc/qrcode-link/qr-code/getUnlimitedQRCode.html
     * @return void
     */
    public function qRCodeAction()
    {
        $appid = $this->getAppid();
        Validate::checkData($this->requestData, [
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
        $app = TaoAppService::applicationHelper()->getMini($appid);
        $response = $app->getClient()->postJson('/wxa/getwxacodeunlimit', $data);

        AppService::responseMimeType(['Content-Type' => 'image/jpeg'], $response->getContent());
    }
}