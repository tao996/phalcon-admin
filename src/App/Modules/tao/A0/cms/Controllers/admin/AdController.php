<?php

namespace App\Modules\tao\A0\cms\Controllers\admin;

use App\Modules\tao\A0\cms\BaseTaoA0CmsController;
use App\Modules\tao\A0\cms\Models\CmsAd;
use App\Modules\tao\sdk\phaxui\Layui\LayuiData;
use Phax\Db\QueryBuilder;
use Phax\Utils\MyData;

/**
 * @property CmsAd $model
 * @rbac ({title:'广告管理'})
 */
class AdController extends BaseTaoA0CmsController
{

    protected string $htmlName = '广告';
    protected array $appendModifyFields = ['at_banner', 'at_index', 'at_list', 'at_page', 'tag', 'gname'];

    public function afterInitialize(): void
    {
        parent::afterInitialize();
        $this->model = new CmsAd();
    }

    protected function indexActionQueryBuilder(QueryBuilder $queryBuilder): void
    {
        if ($this->isResetSearch()) {
            return;
        }
        $status = $this->request->getQuery('status', 'int', 0);
        if ($beginAt = $this->request->getQuery('begin_at')) {
            $queryBuilder->opt('begin_at', '>=', strtotime($beginAt), \PDO::PARAM_INT);
        }
        if (MyData::isBool($this->request->getQuery('active'))) {
            $status = 1;
            $queryBuilder->and(CmsAd::activeCondition(time()), true);
        }
        $queryBuilder->string('tag', $this->request->getQuery('tag'));
        $queryBuilder->int('status', $status);
    }

    /**
     * @rbac ({title:'添加广告'})
     */
    public function addAction()
    {
        return parent::addAction();
    }

    protected array $saveWhiteList = [
        'begin_at',
        'end_at',
        'cover',
        'title',
        'link',
        'kind',
        'at_index',
        'at_list',
        'at_page',
        'at_banner',
        'tag',
        'sort',
        'remark',
        'gname'
    ];

    protected function beforeModelSaveAssign($data): array
    {
        LayuiData::bool2Int($data, ['at_index', 'at_list', 'at_page', 'at_banner']);
        LayuiData::dateTime2Timestamp($data, ['begin_at', 'end_at']);
        return $data;
    }

    /**
     * @rbac ({title:'编辑广告‘})
     * @throws \Exception
     */
    public function editAction()
    {
        if ($this->request->isPost()) {
            return parent::editAction();
        }

        $id = $this->getRequestInt('id');
        $this->model = CmsAd::mustFindFirst($id);
        $row = $this->model->toArray();
        $row['begin_at'] = $row['begin_at'] ? date('Y-m-d H:i:s', $row['begin_at']) : '';
        $row['end_at'] = $row['end_at'] ? date('Y-m-d H:i:s', $row['end_at']) : '';

        return $row;
    }
}