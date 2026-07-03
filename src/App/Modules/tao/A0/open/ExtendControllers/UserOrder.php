<?php
/*
* Copyright (c) 2024-present
* Author: tao996<lvshutao@outlook.com>
* 
* For the full copyright and license information, please view the LICENSE.txt
* file that was distributed with this source code.
*/

namespace App\Modules\tao\A0\open\ExtendControllers;

use App\Modules\tao\A0\open\Models\OpenOrder;
use Phalcon\Di\DiInterface;
use Phax\Support\Exception\BusinessException;

/**
 * @property \Phalcon\Http\Request $request
 * @method int getUserId()
 * @method DiInterface getDI()
 * @method array successPagination()
 * @method mixed getRequestQueryInt()
 */
trait UserOrder
{

    protected function getProjectId(): int
    {
        throw new BusinessException('请实现 getProjectId 方法');
    }

    protected function getOrderQueryBuilder(): \Phax\Db\QueryBuilder
    {
        return OpenOrder::queryBuilder($this->getDI())
            ->int('app', $this->getProjectId())
            ->int('user_id', $this->getUserId());
    }

    /**
     * 订单列表
     * @return array
     * @throws \Exception
     */
    public function indexAction(): array
    {
        $nextid = $this->request->get('nextid', 'int', $this->request->get('next_id', 'int', 0));
        $qb = $this->getOrderQueryBuilder();
        if ($nextid) {
            $qb->where('id <' . $nextid);
        }
        $rows = $qb->orderBy('id desc')
            ->limit(15)
            ->findColumn(
                [
                    'id',
                    'created_at',
                    'amount',
                    'metadata',
                    'success_time',
                    'status',
                    // 退款相关
                    'refund_at',
                    'refund_amt',
                    'refund_status',
                    'refund_time',
                    'refund_amount'
                ]
            );
        return $this->successPagination(0, $rows);
    }

    /**
     * 订单详情
     * @return array
     * @throws \Exception
     */
    public function detailAction(): array
    {
        $id = $this->getRequestQueryInt('id');
        $order = $this->getOrderQueryBuilder()
            ->int('id', $id)
            ->findFirstModel();
        if (empty($order)) {
            throw new BusinessException('没有找到订单');
        }
        return $this->orderDetailResponse($order);
    }

    protected function orderDetailResponse(OpenOrder $order): array
    {
        return $order->toArray();
    }
}