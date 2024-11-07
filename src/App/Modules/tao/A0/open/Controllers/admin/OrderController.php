<?php

namespace App\Modules\tao\A0\open\Controllers\admin;

use App\Modules\tao\A0\open\BaseOpenController;
use App\Modules\tao\A0\open\Models\OpenOrder;


/**
 * @rbac ({title:'订单管理')
 */
class OrderController extends BaseOpenController
{
    protected string $htmlTitle = '订单管理';

    protected string|array $indexQueryColumns = [
        'id',
        'created_at',
        'user_id',
        'channel',
        'trade_type',
        'appid',
        'mchid',
        'amount',
        'status',
        'success_time'
    ];

    public function localInitialize(): void
    {
        $this->model = new OpenOrder();
    }
}