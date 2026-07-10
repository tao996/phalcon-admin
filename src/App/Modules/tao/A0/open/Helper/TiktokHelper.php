<?php

namespace App\Modules\tao\A0\open\Helper;

use Phax\Support\Exception\BusinessException;

class TiktokHelper
{

    /**
     * 对抖音服务端 OpenAPI 接口返回值进行判断
     * @return mixed
     */
    public function openAPIResponseResult($response): mixed
    {
        $data = $response->toArray(true);
        if ($data['err_tips'] != "success") {
            throw new BusinessException($data['err_tips']);
        }
        return $data['data'];
    }
}