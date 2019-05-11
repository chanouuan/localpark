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
        if (!$this->getDb()->transaction(function ($db) use ($entryInfo){
            if (!$db->delete('chemi_entry', 'id = ' . $entryInfo['id'])) {
                return false;
            }
            $entryInfo['log_time'] = date('Y-m-d H:i:s', TIMESTAMP);
            if (!$db->insert('chemi_out', $entryInfo)) {
                return false;
            }
            return true;
        })) {
            return false;
        }
        return true;
    }

}
