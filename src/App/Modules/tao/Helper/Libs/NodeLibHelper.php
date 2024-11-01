<?php

namespace App\Modules\tao\Helper\Libs;

use App\Modules\tao\Models\SystemNode;


class NodeLibHelper
{
    /**
     * 对比数据库已有节点和新的节点
     * @param array $dbNodes 数据库节点
     * @param array $newNodes
     * @return array [delete=>需要删除的节点, update=>需要更新的节点, append=>新增的节点]
     */
    public static function compare(array $dbNodes, array $newNodes): array
    {
        foreach ($dbNodes as $hasIndex => $dbNode) {
            foreach ($newNodes as $index => $node) {
                if (isset($node['find'])) {
                    continue;
                }

                if (self::sameNode($dbNode, $node)) {
                    $newNodes[$index]['find'] = 1;
                    $dbNodes[$hasIndex]['action'] = 'find';

                    if ($dbNode['title'] != $node['title'] || $dbNode['is_auth'] != $node['is_auth']) {
                        $dbNodes[$hasIndex]['title'] = $node['title'];
                        $dbNodes[$hasIndex]['is_auth'] = $node['is_auth'];
                        $dbNodes[$hasIndex]['action'] = 'update'; // 需要修改
                    }
                    break;
                }
            }
        }
        $rows = [];
        $rows['delete'] = array_filter($dbNodes, function ($row) {
            return !isset($row['action']);
        });
        $rows['update'] = array_filter($dbNodes, function ($row) {
            return isset($row['action']) && $row['action'] == 'update';
        });

        $rows['append'] = array_filter($newNodes, function ($row) {
            return !isset($row['find']);
        });

        return $rows;
    }

    /**
     * 比较两个节点的基本信息是否相同
     * @param $node1
     * @param $node2
     * @return bool
     */
    public static function sameNode($node1, $node2): bool
    {
        return $node1['module'] == $node2['module']
            && $node1['node'] == $node2['node']
            && $node1['type'] == $node2['type']
            && $node1['kind'] == $node2['kind'];
    }

    /**
     * 找出指定节点的子控制器 Controller 及其操作 Action 节点
     * @param array $node
     * @param array $nodes
     * @return array
     */
    private static function childrenNodeTree(array $node, array $nodes)
    {
        $node['isParent'] = true;
        $node['pid'] = 0;
        $node['children'] = [];

        foreach ($nodes as $ctrlNode) {
            if ($ctrlNode['kind'] == $node['kind']
                && $ctrlNode['type'] == SystemNode::TYPE_CONTROLLER
                && $ctrlNode['module'] == $node['module']
            ) {
                $ctrlNode['isParent'] = true;
                $ctrlNode['pid'] = $node['id'] ?? 0;
                $ctrlNode['children'] = [];

                foreach ($nodes as $actionNode) {
                    if ($actionNode['kind'] == $node['kind']
                        && $actionNode['type'] == SystemNode::TYPE_ACTION
                        && $actionNode['module'] == $node['module']
                        && str_starts_with($actionNode['node'], $ctrlNode['node'])
                    ) {
                        $actionNode['pid'] = $ctrlNode['id'] ?? 0;
                        $ctrlNode['children'][] = $actionNode;
                    }
                }

                $node['children'][] = $ctrlNode;
            }
        }
        return $node;
    }

    /**
     * 将一维节点列表转为 Layui.Tree 格式的节点
     * @param array $nodes
     * @return array
     * @throws \Exception
     */
    public static function nodeTree(array $nodes)
    {
        $rows = [];
        foreach ($nodes as $node) {
            if ($node['kind'] == SystemNode::KIND_PROJECT) {
                if ($node['type'] == SystemNode::TYPE_MODULE) {
                    $rows[] = self::childrenNodeTree($node, $nodes);
                }
            } elseif ($node['kind'] == SystemNode::KIND_MODULE) {
                if ($node['type'] == SystemNode::TYPE_MODULE) {
                    $rows[] = self::childrenNodeTree($node, $nodes);
                }
            } else {
                throw new \Exception('unknown node.kind value');
            }
        }
        return $rows;
    }

}