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
        ], 'id,car_type,current_node_id,last_nodes,update_time');
        if ($info) {
            // 节点记录 {{node_id:time}}
            $info['last_nodes'] = json_decode($info['last_nodes'], true);
        }
        return $info;
    }

    /**
     * 获取通过指定节点的入场车
     * @param array $nodes_id 节点列表
     * @return array
     */
    public function getEntryCurrentNodeCar (array $nodes_id)
    {
        $list = $this->select([
            'current_node_id' => ['in', $nodes_id]
        ], 'car_number');
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
