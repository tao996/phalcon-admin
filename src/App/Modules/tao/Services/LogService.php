<?php

declare(strict_types=1);

namespace App\Modules\tao\Services;


use App\Modules\tao\Helper\MyMvcHelper;

class LogService
{

    public function __construct(public MyMvcHelper $mvc)
    {
    }

    /**
     * 添加一条操作日志
     * @throws \Exception
     */
    public function insert(string $title, string $action): void
    {
        return;
        // todo 暫時不記錄日誌
//        $m = new SystemLog();
//        $r = request();
//        $m->assign([
//            'user_id' => LoginUser::instance()->userId(),
//            'url' => $r->getURI(),
//            'method' => $r->getMethod(),
//            'action' => $action,
//            'title' => $title,
//            'ip' => $r->getClientAddress(),
//            'useragent' => $r->getUserAgent(),
//        ]);
//        if (!$m->save()) {
//            throw new \Exception($m->getFirstError());
//        }
    }
}