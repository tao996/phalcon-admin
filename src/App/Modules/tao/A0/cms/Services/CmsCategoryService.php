<?php

namespace App\Modules\tao\A0\cms\Services;

use App\Modules\tao\A0\cms\Helper\MyCmsMvcHelper;
use App\Modules\tao\A0\cms\Models\CmsCategory;
use App\Modules\tao\sdk\phaxui\Layui\LayuiData;

class CmsCategoryService
{
    public function __construct(protected MyCmsMvcHelper $cms)
    {
    }

    /**
     * 栏目列表，供文章发布时使用
     * @return array
     * @throws \Exception
     */
    public function options(): array
    {
        $list = CmsCategory::queryBuilder()
            ->int('status', 1)
            ->columns('id,pid,title,kind')->find();
        foreach ($list as $index => $item) {
            $list[$index]['otitle'] = $item['title']; // 备份原始标题
            $list[$index]['title'] = '[' . CmsCategory::mapKind($item['kind']) . '] ' . $item['title']; // kind
        }
        return LayuiData::selectOptions(0, $list);
    }

    /**
     * 查询指定的栏目
     * @param int $id 栏目 ID
     * @param array $columns 待查询的列，默认全部
     * @param bool $mustGet 记录是否必须存在，默认为 true
     * @return array
     * @throws \Exception
     */
    public function getRecord(int $id, array $columns = [], bool $mustGet = true): array
    {
        if ($id < 1) {
            throw new \Exception('待查询的栏目 ID 不能为空');
        }
        $row = CmsCategory::queryBuilder()->int('id', $id)
            ->columns($columns)
            ->findFirstArray();
        if (empty($row) && $mustGet) {
            throw new \Exception('找不到符合要求的栏目记录');
        }
        return $row;
    }
}