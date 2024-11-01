<?php

namespace App\Modules\tao\Services;

use App\Modules\tao\Helper\Libs\NodeLibHelper;
use App\Modules\tao\Helper\MyMvcHelper;
use App\Modules\tao\Models\SystemNode;
use App\Modules\tao\Models\SystemRoleNode;
use Phax\Support\Facade\MyHelperFacade;
use Phax\Utils\MyData;

class NodeService
{
    public function __construct(public MyMvcHelper $mvc)
    {
    }

    /**
     * 获取指定角色的可访问节点
     * @param string|array $role_ids 用户的角色 ID
     * @return array ['ca1', 'ca2', 'ca3', ...]
     */
    public function findNodeListByRoleIds(string|array $role_ids): array
    {
        $nodeList = [];
        if (is_array($role_ids)) {
            if ($role_ids = array_unique(MyData::getInts($role_ids))) {
                $role_ids = join(',', $role_ids);
            }
        }
        if (!empty($role_ids)) {
            $nodeListSQL = 'SELECT node FROM tao_system_node WHERE id IN (SELECT node_id FROM tao_system_role_node WHERE role_id IN (SELECT id FROM tao_system_role WHERE id IN (' . $role_ids . ')))';
            $rows = $this->mvc->db()->query($nodeListSQL)->fetchAll(\PDO::FETCH_ASSOC);
            $nodeList = MyHelperFacade::pluck($rows, 'node');
        }
        return $nodeList;
    }


    /**
     * 根据角色 ID 获取授权节点
     * @return array
     * @throws \Exception
     */
    public function getAuthorizeNodeListByRoleId(int $roleId): array
    {
        if (empty($roleId)) {
            return [];
        }
        $bindNodes = SystemRoleNode::queryBuilder()
            ->int('role_id', $roleId)->columns('node_id')
            ->find();
        $bindNodeIds = array_column($bindNodes, 'node_id');
// 全部的的节点
        $nodeList = SystemNode::queryBuilder()
            ->int('is_auth', 1)
            ->field('id,node,title,type,is_auth')
            ->find();
        // 重新排
        $newNodeList = [];
        foreach ($nodeList as $vm) {
            // 模块
            if ($vm['type'] == SystemNode::TYPE_MODULE) {
                $vm = array_merge($vm, ['field' => 'node']);
                $vm['title'] = "{$vm['title']}【{$vm['node']}】";
                $vm['children'] = [];
                $hasModuleSpread = false;

                foreach ($nodeList as $vc) {// 控制器
                    if ($vc['type'] == SystemNode::TYPE_CONTROLLER && str_starts_with($vc['node'], $vm['node'])) {
                        $vc = array_merge($vc, ['field' => 'node']);
                        $vc['checked'] = false;
                        $vc['title'] = "{$vc['title']}【{$vc['node']}】";
                        $hasControllerSpread = false;

                        $children = [];
                        foreach ($nodeList as $v) {// 操作
                            if ($v['type'] == SystemNode::TYPE_ACTION && str_starts_with($v['node'], $vc['node'])) {
                                $v = array_merge($v, ['field' => 'node', 'spread' => false]);
                                $v['checked'] = in_array($v['id'], $bindNodeIds);
                                if ($v['checked']) {
                                    $hasModuleSpread = true;
                                    $hasControllerSpread = true;
                                }
                                $v['title'] = "{$v['title']}【{$v['node']}】";
                                $children[] = $v;
                            }
                        }
                        $vc['children'] = $children ?: [];
                        $vc['spread'] = $hasControllerSpread;
                        $vm['children'][] = $vc;
                    }
                }
                $vm['spread'] = $hasModuleSpread;
                $newNodeList[] = $vm;
            }
        }
        return $newNodeList;
    }

    /**
     * 对比数据库已有节点和新的节点
     * @param array $dbNodes 数据库节点
     * @param array $newNodes
     * @return array [delete=>需要删除的节点, update=>需要更新的节点, append=>新增的节点]
     */
    public function nodesCompare(array $dbNodes, array $newNodes): array
    {
        return NodeLibHelper::compare($dbNodes, $newNodes);
    }

    /**
     * 比较两个节点的基本信息是否相同
     * @param $node1
     * @param $node2
     * @return bool
     */
    public  function sameNode($node1, $node2): bool
    {
        return NodeLibHelper::sameNode($node1, $node2);
    }
    /**
     * 将一维节点列表转为 Layui.Tree 格式的节点
     * @param array $nodes
     * @return array
     * @throws \Exception
     */
    public  function nodeTree(array $nodes): array
    {
        return NodeLibHelper::nodeTree($nodes);
    }
}