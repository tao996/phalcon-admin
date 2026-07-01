<?php

namespace App\Modules\tao\A0\open;

use App\Modules\tao\A0\open\Data\Config;
use App\Modules\tao\A0\open\Helper\MyOpenMvcHelper;
use App\Modules\tao\BaseController;
use App\Modules\tao\Helper\MyMvcHelper;
use Phax\Support\Exception\BusinessException;

/**
 * 小程序
 * @property MyMvcHelper $vv
 */
class BaseOpenMiniController extends BaseController
{
    public int $userId = 0;

    public MyOpenMvcHelper $openMvcHelper;

    protected function afterInitialize(): void
    {
        $this->openMvcHelper = $this->vv->a0openHelper();
        $this->localInitialize();
    }

    protected function localInitialize(): void
    {
    }

    public function getUserId(): int
    {
        if ($this->userId < 1) {
            $this->userId = $this->loginUser()->id;
        }
        return $this->userId;
    }

    /**
     * 获取客户端请求的 ID
     * @return string
     */
    protected function getAppid(): string
    {
        $appid = $this->request->getQuery('appid', 'string', '');
        if (empty($appid)) {
            throw new BusinessException('必须指定 appid');
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
     * 获取下一次查询的起始 ID，通常用于客户端的 loadMore 操作
     * @return int
     */
    protected function getNextId(): int
    {
        return $this->request->getQuery('next_id', 'int', $this->request->getQuery('nextid', 'int', 0));
    }

    protected function getLimit(): int
    {
        return $this->request->get('limit', 'int', 0);
    }

    /**
     * 客户端平台
     * @return int
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
        throw new BusinessException('could not find platform from query');
    }
}