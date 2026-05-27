<?php

namespace App\Modules\tao\A0\cms\Controllers\admin;

use App\Modules\tao\A0\cms\BaseTaoA0CmsController;
use App\Modules\tao\A0\cms\Models\CmsAd;
use Phax\Db\QueryBuilder;
use Phax\Utils\MyData;

/**
 * @property CmsAd $model
 * @rbac ({title:'广告管理'})
 */
class AdController extends BaseTaoA0CmsController
{

    protected string $htmlTitle = '广告';
    protected array $appendModifyFields = ['at_banner', 'at_index', 'at_list', 'at_page', 'tag', 'gname'];

    public function localInitialize(): void
    {
        $this->model = new CmsAd();
    }

    protected function beforeIndexQuery(QueryBuilder $queryBuilder): void
    {
        if ($this->isResetSearch()) {
            return;
        }
        $status = $this->request->getQuery('status', 'int', 0);
        if ($beginAt = $this->request->getQuery('begin_at')) {
            $queryBuilder->opt('begin_at', '>=', $beginAt);
        }
        if (MyData::isBool($this->request->getQuery('active'))) {
            $status = 1;
            $queryBuilder->and(CmsAd::activeCondition(time()), true);
        }
        $queryBuilder->string('tag', $this->request->getQuery('tag'));
        $queryBuilder->int('status', $status);
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

    public array $modelBool2IntColumns = [
        'at_index',
        'at_list',
        'at_page',
        'at_banner'
    ];
}