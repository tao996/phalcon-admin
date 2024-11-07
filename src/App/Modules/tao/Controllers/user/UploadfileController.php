<?php

namespace App\Modules\tao\Controllers\user;

use App\Modules\tao\BaseController;
use App\Modules\tao\Models\SystemUploadfile;
use Phax\Db\QueryBuilder;

/**
 * 文件管理
 */
class UploadfileController extends BaseController
{
    protected string $htmlTitle = '文件管理';

    protected array|string $userActions = '*';
    public array $enableActions = ['index', 'modify', 'add', 'delete'];

    protected array $allowModifyFields = ['summary'];
    protected string|array $indexQueryColumns = [
        'id',
        'upload_type',
        'summary',
        'url',
        'width',
        'height',
        'file_size',
        'created_at'
    ];

    public function afterInitialize(): void
    {
        $this->model = new SystemUploadfile();
    }

    protected function indexActionQueryBuilder(QueryBuilder $queryBuilder): void
    {
        if (!$this->vv->loginUserHelper()->isSuperAdmin()) {
            $queryBuilder->int('user_id', $this->loginUser()->id);
        }
        if ($keyword = $this->request->getQuery('title')) {
            $queryBuilder->like('summary', $keyword);
        }

        parent::indexActionQueryBuilder($queryBuilder);
    }
}