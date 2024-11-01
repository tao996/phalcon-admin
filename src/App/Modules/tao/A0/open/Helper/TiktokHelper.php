<?php

namespace App\Modules\tao\A0\open\Helper;

class TiktokHelper
{
    public function __construct(private MyOpenMvcHelper $helper)
    {
    }

    /**
     * 对抖音服务端 OpenAPI 接口返回值进行判断
     * @return mixed
     */
    public function openAPIResponseResult($response)
    {
        $data = $response->toArray(true);
        if ($data['err_tips'] != "success") {
            throw new \Exception($data['err_tips']);
        }
        return $data['data'];
    }
}