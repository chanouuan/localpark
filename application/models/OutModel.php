<?php

namespace app\models;

use Crud;

class OutModel extends Crud {

    protected $table = 'chemi_out';

    /**
     * 添加出入场信息
     * @param $entry_id 入场记录ID
     * @return bool
     */
    public function addOutInfo ($entry_id)
    {
        if (empty($entryInfo = (new EntryModel())->find(['id' => $entry_id]))) {
            return false;
        }
        // chemi_out 为 MyISAM，不能用事务
        $entryInfo['log_time'] = date('Y-m-d H:i:s', TIMESTAMP);
        if (!$this->getDb()->insert('chemi_out', $entryInfo)) {
            return false;
        }
        if (!$this->getDb()->delete('chemi_entry', 'id = ' . $entry_id)) {
            $this->getDb()->delete('chemi_out', 'id = ' . $entry_id);
            return false;
        }
        return true;
    }

    /**
     * 获取上次出场时间
     * @param $car_number 车牌号
     * @return timestamp
     */
    public function getLastOutParkTime ($car_number)
    {
        $info = $this->find([
            'car_number' => $car_number, 'dot' => ['in', [\app\common\DotType::END_DOT, \app\common\DotType::END_START_DOT]]
        ], 'update_time', 'id desc');
        return $info['update_time'] ? strtotime($info['update_time']) : 0;
    }

}
