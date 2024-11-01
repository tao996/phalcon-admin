<?php

namespace App\Modules\tao\Controllers\user;

use App\Modules\tao\BaseController;
use App\Modules\tao\sdk\qiniu\QiniuDriver;

class QiniuController extends BaseController
{
    protected array|string $userActions = '*';

    /**
     * 生成客户端上传凭证
     * /api/m/tao/user.qiniu/index
     * @return array
     */
    public function indexAction()
    {
        if (!is_debug()) {
            throw new \Exception('only run in debug mode');
        }
        $this->jsonResponse = true;
        $this->vv->configService()->forceCache();

        $qiniu = new QiniuDriver($this->vv->configService()->uploadConfig());
        return [
            'token' => $qiniu->imageToken(),
            'expire' => time() + 7100,
            'domain' => $qiniu->getDomain(),
        ];
    }
}