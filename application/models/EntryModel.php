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

}
