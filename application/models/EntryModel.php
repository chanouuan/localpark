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
        return $this->find([
            'car_number' => $car_number
        ], 'id,car_type,current_node_id,last_nodes,update_time');
    }

}