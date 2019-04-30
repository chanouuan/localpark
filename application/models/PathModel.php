<?php

namespace app\models;

use Crud;

class PathModel extends Crud {

    protected $table = 'chemi_path';

    /**
     * 获取临时车路径
     */
    public function getTempCarPath ()
    {
        $list = $this->select(['allow_temp_car' => 1], 'id,start_node,end_node,nodes,allow_temp_car,allow_invalid_car,allow_child_car');
        if ($list) {
            foreach ($list as $k => $v) {
                $list[$k]['nodes'] = json_decode($v['nodes'], true);
            }
        }
        return $list;
    }

    /**
     * 获取路径节点
     */
    public function getPathNodeById ($paths)
    {
        $list = $this->select(['id' => ['in', $paths]], 'id,start_node,end_node,nodes,allow_temp_car,allow_invalid_car,allow_child_car');
        if ($list) {
            foreach ($list as $k => $v) {
                $list[$k]['nodes'] = json_decode($v['nodes'], true);
            }
        }
        return $list;
    }

    /**
     * 获取指定节点的上一个节点
     */
    public function getLastNodeId ($node_id, $depth = 1)
    {
        $list = $this->select('JSON_CONTAINS(nodes,"' . $node_id . '")', 'id,nodes');
        $nodes = [];
        if ($list) {
            foreach ($list as $k => $v) {
                $v['nodes'] = json_decode($v['nodes'], true);
                if ($key = array_search($node_id, $v['nodes'])) {
                    if (isset($v['nodes'][$key - $depth])) {
                        $nodes[] = $v['nodes'][$key - $depth];
                    }
                }
            }
            unset($list);
            $nodes = array_unique(array_filter($nodes));
        }
        return $nodes;
    }

}
