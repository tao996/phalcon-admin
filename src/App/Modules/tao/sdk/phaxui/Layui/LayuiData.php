<?php

namespace App\Modules\tao\sdk\phaxui\Layui;

class LayuiData
{
    /**
     * 对数据进行格式化，以便适用于 layui.treeTable 格式
     * @link https://layui.dev/docs/2/treeTable/
     * @param array $rows 必须包含 pid
     * @return array
     */
    public static function treeTable(array $rows)
    {
        $items = [];
        foreach ($rows as $value) {
            $value['isParent'] = false;
            $items[$value['id']] = $value;
        }
        $tree = [];
        foreach ($items as $key => $value) {
            if (isset($items[$value['pid']])) {
                $items[$value['pid']]['children'][] = &$items[$key];
                $items[$value['pid']]['isParent'] = true;
            } else {
                $tree[] = &$items[$key];
            }
        }
        return $tree;
    }


    /**
     * 将数据格式化成使用 select>option 的的层次数据
     * @param $pid int 父级 ID
     * @param $list array 一维列表数据
     * @param $level int 当前层级
     * @return array
     */
    public static function selectOptions(int $pid, array $list, int $level = 0): array
    {
        $newList = [];
        foreach ($list as $vo) {
            if ($vo['pid'] == $pid) {
                $level++;
                foreach ($newList as $v) {
                    if ($vo['pid'] == $v['pid'] && isset($v['level'])) {
                        $level = $v['level'];
                        break;
                    }
                }
                $vo['level'] = $level;
                if ($level > 1) {
                    $repeatString = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
                    $markString = str_repeat("{$repeatString}├{$repeatString}", $level - 1);
                    $vo['title'] = $markString . $vo['title'];
                }
                $newList[] = $vo;
                $childList = self::selectOptions($vo['id'], $list, $level);
                !empty($childList) && $newList = array_merge($newList, $childList);
            }

        }
        return $newList;
    }

    /**
     * 时间范围
     * @param string|null $dtRange 时间范围字符串，示例 2024-01-24 - 2024-02-25
     * @param bool $timestamp 是否默认为时间戳，默认 true
     * @return array|null
     */
    public static function dateRange(string|null $dtRange, bool $timestamp = true): array|null
    {
        if (empty($dtRange)) {
            return null;
        }
        $dt = explode(' - ', $dtRange);

        $start = strtotime($dt[0]);
        if ($start === false) {
            throw new \Exception($dt[0] . ' is not a valid start date time');
        }
        $end = strtotime($dt[1]);
        if ($end === false) {
            throw new \Exception($dt[1] . ' is not a valid end date time');
        }
        return $timestamp ? [$start, $end] : $dt;
    }
}