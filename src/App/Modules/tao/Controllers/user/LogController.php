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
    protected string $htmlName = '日志';

    public function afterInitialize(): void
    {
        $this->model = new SystemLog();
    }

    protected function indexActionGetResult(int $count, QueryBuilder $queryBuilder): array
    {
        $queryBuilder->join(SystemUser::class, 'nickname', 'user_id');
        return parent::indexActionGetResult($count, $queryBuilder);
    }
}