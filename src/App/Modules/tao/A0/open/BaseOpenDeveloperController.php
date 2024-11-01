<?php

namespace App\Modules\tao\A0\open;

use App\Modules\tao\A0\open\Data\Config;
use App\Modules\tao\A0\open\Helper\MyOpenMvcHelper;
use App\Modules\tao\BaseController;
use App\Modules\tao\Helper\Auth\LoginAppAuthAdapter;
use App\Modules\tao\Helper\Auth\LoginDemoTokenAuthAdapter;
use App\Modules\tao\Helper\MyMvcHelper;

/**
 * @property MyMvcHelper $vv
 */
class BaseOpenDeveloperController extends BaseController
{
    /**
     * @var array 前端请求数据
     */
    public array $requestData = [];
    public MyOpenMvcHelper $mvc;

    public function initialize(): void
    {
        if ($this->di->has('view')) {
            $this->view->disable();
        }
        $this->jsonResponse = true;
        // just for unit test
        if ($this->request->hasHeader(LoginDemoTokenAuthAdapter::HeaderKeyName)) {
            $this->setLoginAdapter(LoginDemoTokenAuthAdapter::class);
        } else {
            $this->setLoginAdapter(LoginAppAuthAdapter::class);
        }
        parent::initialize();
    }

    protected function afterInitialize(): void
    {
        $this->mvc = new MyOpenMvcHelper($this->vv);
        $this->requestData = $this->request->getJsonRawBody(true) ?: [];
        $this->localInitialize();
    }

    protected function localInitialize(): void
    {
    }

    public function afterExecuteRoute(\Phalcon\Mvc\Dispatcher $dispatcher): void
    {
        if ($this->response->isSent()) {
            return;
        }
        $data = $dispatcher->getReturnedValue();
        if ($data instanceof \Psr\Http\Message\ResponseInterface) {
            $this->mvc->wechatHelper()->response($data);
            return;
        }
        // 小程序总是 api response
        if (is_array($data) && isset($data['msg']) && isset($data['code'])) {
            $this->doResponse(true, $data);
        } else {
            $this->doResponse(true, $this->success('', $data));
        }
    }

    protected function getAppid(): string
    {
        $appid = $this->request->getQuery('appid', 'string', '');
        if (empty($appid)) {
            throw new \Exception('必须指定 appid');
        }
        // todo 检查 appid 是否合法
        return $appid;
    }

    /**
     * 获取商户 ID
     * @return string
     */
    protected function getMchid(): string
    {
        return $this->request->getQuery('mchid', 'string', '');
    }

    /**
     * @return int
     * @throws \Exception
     */
    protected function mustGetPlatform(): int
    {
        $pl = $this->request->getQuery('platform', 'string', '');
        switch ($pl) {
            case 'tt':
            case 'tiktok':
                return Config::Tiktok;
            case 'weapp':
            case 'wechat':
                return Config::Wechat;
        }
        throw new \Exception('could not find platform from query');
    }
}