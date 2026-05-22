<?php

namespace App\Modules\tao\Controllers\user;

use App\Modules\tao\BaseController;
use App\Modules\tao\Models\SystemLog;
use App\Modules\tao\Models\SystemUser;
use Phax\Db\QueryBuilder;

/**
 * @property SystemLog $model
 */
class LogController extends BaseController
{
    protected array|string $userActions = '*';
    protected string $htmlTitle = '日志';

    public function afterInitialize(): void
    {
        $this->model = new SystemLog();
    }

    protected function indexActionGetResult(int $count, QueryBuilder $queryBuilder): array
    {
        $rows = parent::indexActionGetResult($count, $queryBuilder);
        if (empty($rows)) {
            return $rows;
        }
        // 批量填充用户昵称（替代已移除的 QueryBuilder::join）
        $uids = array_unique(array_filter(array_column($rows, 'user_id')));
        if ($uids) {
            $users = SystemUser::find([
                'columns' => 'id, nickname',
                'conditions' => 'id IN ({ids:array})',
                'bind' => ['ids' => $uids],
            ])->toArray();
            $userMap = array_column($users, 'nickname', 'id');
            foreach ($rows as &$row) {
                $row['nickname'] = $userMap[$row['user_id']] ?? '';
            }
            unset($row);
        }
        return $rows;
    }
}