<?php

namespace app\models;

use Crud;

class EntryModel extends Crud {

    protected $table = 'chemi_entry';

    /**
     * 获取在场车辆信息
     * @param $car_number
     * @return array
     */
    public function getCarInfo ($car_number)
    {
        $info = $this->find([
            'car_number' => $car_number
        ], 'id,car_type,paths,current_node_id,last_nodes,update_time');
        if ($info) {
            // 当前路径
            $info['paths'] = json_decode($info['paths'], true);
            // 节点记录 {{node_id:{time:time}}}
            $info['last_nodes'] = json_decode($info['last_nodes'], true);
        }
        return $info;
    }

    /**
     * 自动修补路线
     * @param $node_id 当前节点ID
     * @param $car_number 纠正后的车牌号
     * @return bool
     */
    public function autoRepairPath ($node_id, $car_number)
    {
        if (!$entryCarInfo = $this->getCarInfo($car_number)) {
            return false;
        }
        // 判断哪条路径可以到达当前节点node_id
        // 如果有多条路径可以到达，就只取其中一条，所以这里用find
        if (!$pathInfo = $this->getDb()->table('chemi_path')->field('id,nodes')->where('id in (' . implode(',', $entryCarInfo['paths']) . ') and JSON_CONTAINS(nodes,"' . $node_id . '")')->find()) {
            return false;
        }
        // 获取到达当前节点，需要经过的点
        $pathInfo['nodes'] = json_decode($pathInfo['nodes'], true);
        if (false === ($currentNodekey = array_search($entryCarInfo['current_node_id'], $pathInfo['nodes']))) {
            return false;
        }
        if (!$nodeKey = array_search($node_id, $pathInfo['nodes'])) {
            // 当前节点是起点
            return true;
        }
        if ($nodeKey - $currentNodekey <= 1) {
            // 节点无需修补
            return true;
        }
        $passNodes = array_slice($pathInfo['nodes'], $currentNodekey + 1, $nodeKey - $currentNodekey - 1);
        if (empty($passNodes)) {
            return false;
        }
        // 按平均法修补节点
        $diffTime = intval((TIMESTAMP - strtotime($entryCarInfo['update_time'])) / (count($passNodes) + 1));
        foreach ($passNodes as $k => $v) {
            $entryCarInfo['last_nodes'][] = [
                $v => [
                    'time' => date('Y-m-d H:i:s', $entryCarInfo['update_time'] + ($diffTime * ($k + 1))),
                    'auto' => 1
                ]
            ];
        }
        return $this->getDb()->update($this->table, [
            'paths' => json_encode([$pathInfo['id']]),
            'current_node_id' => end($passNodes),
            'last_nodes' => json_encode($entryCarInfo['last_nodes']),
            'update_time' => date('Y-m-d H:i:s', TIMESTAMP)
        ], 'id = '. $entryCarInfo['id']);
    }

    /**
     * 获取通过指定节点的入场车
     * @param array $nodes_id 节点列表
     * @param array $path_id 路径列表
     * @return array
     */
    public function getEntryCurrentNodeCar (array $nodes_id, array $path_id)
    {
        $condition = [
            'current_node_id in (' . implode(',', $nodes_id) . ')'
        ];
        $param = [];
        foreach ($path_id as $k => $v) {
            $param[] = 'JSON_CONTAINS(paths,' . $v . ')';
        }
        $param = '(' . implode(' OR ', $param) . ')';
        $condition[] = $param;
        $list = $this->select($condition, 'car_number');
        if ($list) {
            $list = array_column($list, 'car_number');
        }
        return $list;
    }

    /**
     * 获取所有入场车
     * @return array
     */
    public function getAllEntryCar ()
    {
        $list = $this->select(null, 'car_number');
        if ($list) {
            $list = array_column($list, 'car_number');
        }
        return $list;
    }

}
