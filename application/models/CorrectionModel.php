<?php

namespace app\models;

use Crud;

class CorrectionModel extends Crud {

    protected $table = 'chemi_correction_record';

    /**
     * 获取车牌纠正记录
     * @param $id ID
     * @return array
     */
    public function getRecord (array $id)
    {
        if (!$list = $this->select(['id' => ['in', $id]], 'id,node_id,original_car_number,car_number,error_scene,error_count,message,scene_result')) {
            return [];
        }
        foreach ($list as $k => $v) {
            $list[$k]['message'] = json_decode($v['message'], true);
        }
        return $list;
    }

}
