<?php

namespace app\models;

use Crud;

class NodeModel extends Crud {

    protected $table = 'chemi_node';

    /**
     * 获取异常车通行方式
     * @param $node_id 节点ID
     * @return array
     */
    public function getAbnormalCarPassWay ($node_id)
    {
        $nodeInfo = $this->find(['id' => $node_id], 'abnormal_car_pass_way,abnormal_car_charge');
        return $nodeInfo;
    }

    /**
     * 获取节点信息
     * @param $node_id 节点ID
     * @return array
     */
    public function getNode ($node_id)
    {
        return $this->find(['id' => $node_id], 'id,temp_car_count,temp_car_left');
    }

}
