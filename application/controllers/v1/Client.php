<?php

namespace app\controllers;

use ActionPDO;
use app\common\CarType;
use app\common\PassType;
use app\common\PayType;
use app\library\DB;
use app\models\CorrectionModel;
use app\models\EntryModel;
use app\models\OutModel;
use app\models\NodeModel;

class Client extends ActionPDO {

    public function __style()
    {
        return 'default';
    }

    public function layout ()
    {
        $model = new NodeModel();
        $nodes = $model->select(null);

        return [
            'nodes' => $nodes
        ];
    }

    public function test ()
    {
        $randCarType = [2, 3, 4, 5];
        $list = DB::getInstance()->table('car_number')->field('car_number,rand() as r')->order('r')->limit(20)->select();
        $list = array_chunk($list, 10);

        foreach ($list[0] as $k => $v) {
            if (!check_car_license($v['car_number'])) {
                continue;
            }
            $rand = array_rand($randCarType);
            $carType = $randCarType[$rand];
            $data = [
                'car_number' => $v['car_number'],
                'car_type' => $carType,
            ];
            if ($carType == 2) {
                $data['start_time'] = date('Y-m-d H:i:s');
                $data['end_time'] = date('Y-m-d H:i:s', TIMESTAMP + (86400 * rand(1,30)));
            } else if ($carType == 3) {
                $data['end_time'] = date('Y-m-d H:i:s', TIMESTAMP + (86400 * rand(1,30)));
            } else if ($carType == 4) {
                $data['end_time'] = date('Y-m-d H:i:s', TIMESTAMP + (86400 * rand(1,30)));
            } else if ($carType == 5) {
                $data['balance'] = rand(1,1000);
            }
            $carId = DB::getInstance()->insert('chemi_car', $data, false, false, true);
            $carPathId = DB::getInstance()->insert('chemi_car_path', [
                'car_id' => $carId,
                'path_id' => rand(2,3),
                'car_number' => json_unicode_encode([$data['car_number'], $list[1][$k]['car_number']]),
                'place_count' => 1,
                'place_left' => 1
            ], false, false, true);
            DB::getInstance()->insert('chemi_car_child', [
                ['car_path_id' => $carPathId, 'car_number' => $data['car_number']],
                ['car_path_id' => $carPathId, 'car_number' => $list[1][$k]['car_number']]
            ]);
        }
        echo 'ok';
    }

    /**
     * 入场记录查询
     */
    public function getEntryList ()
    {
        $model = new EntryModel();
        $info = $model->find(null, 'count(*) as count');
        $count = $info['count'];
        $pagesize = getPageParams(getgpc('page'), $count);
        $list = $model->select(null, null, 'id desc', $pagesize['limitstr']);
        if ($list) {
            $corrections = [];
            foreach ($list as $k => $v) {
                $list[$k]['correction_record'] = array_filter(json_decode($v['correction_record'],true));
                $corrections = array_merge($corrections, $list[$k]['correction_record']);
            }
            if ($corrections) {
                $corrections = (new CorrectionModel())->getRecord($corrections);
                if ($corrections) {
                    $corrections = array_column($corrections, null, 'id');
                    foreach ($corrections as $k => $v) {
                        $corrections[$k]['mark'][] = '车牌号：' . $v['original_car_number'];
                        $corrections[$k]['mark'][] = '纠正后：' . $v['car_number'];
                        $corrections[$k]['mark'][] = '错误场景：' . $v['error_scene'];
                        $corrections[$k]['mark'][] = '纠正次数：' . $v['error_count'];
                        foreach ($v['message'] as $kk => $vv) {
                            $corrections[$k]['mark'][] = implode('，', $vv);
                        }
                    }
                }
            }
            $nodes = (new NodeModel())->select(['id' => ['in', array_column($list, 'current_node_id')]], 'id,name');
            $nodes = array_column($nodes, null, 'id');
            foreach ($list as $k => $v) {
                $list[$k]['car_type'] = CarType::getMessage($v['car_type']);
                $list[$k]['current_car_type'] = CarType::getMessage($v['current_car_type']);
                $list[$k]['pass_type'] = PassType::getMessage($v['pass_type']);
                $list[$k]['current_node_name'] = $nodes[$v['current_node_id']]['name'];
                $list[$k]['money'] = round_dollar($v['money']);
                foreach ($list[$k]['correction_record'] as $kk => $vv) {
                    $list[$k]['correction_record'][$kk] = implode('<br/>', $corrections[$vv]['mark']);
                }
                $list[$k]['correction_record'] = implode('<hr>', $list[$k]['correction_record']);
            }
        }
        return success([
            'total' => $count,
            'list' => $list
        ]);
    }

    /**
     * 出场记录查询
     */
    public function getOutList ()
    {
        $model = new OutModel();
        $info = $model->find(null, 'count(*) as count');
        $count = $info['count'];
        $pagesize = getPageParams(getgpc('page'), $count);
        $list = $model->select(null, null, 'id desc', $pagesize['limitstr']);
        if ($list) {
            $corrections = [];
            foreach ($list as $k => $v) {
                $list[$k]['correction_record'] = array_filter(json_decode($v['correction_record'],true));
                $corrections = array_merge($corrections, $list[$k]['correction_record']);
            }
            if ($corrections) {
                $corrections = (new CorrectionModel())->getRecord($corrections);
                if ($corrections) {
                    $corrections = array_column($corrections, null, 'id');
                    foreach ($corrections as $k => $v) {
                        $corrections[$k]['mark'][] = '车牌号：' . $v['original_car_number'];
                        $corrections[$k]['mark'][] = '纠正后：' . $v['car_number'];
                        $corrections[$k]['mark'][] = '错误场景：' . $v['error_scene'];
                        $corrections[$k]['mark'][] = '纠正次数：' . $v['error_count'];
                        foreach ($v['message'] as $kk => $vv) {
                            $corrections[$k]['mark'][] = implode('，', $vv);
                        }
                    }
                }
            }
            $nodes = (new NodeModel())->select(['id' => ['in', array_column($list, 'current_node_id')]], 'id,name');
            $nodes = array_column($nodes, null, 'id');
            foreach ($list as $k => $v) {
                $list[$k]['car_type'] = CarType::getMessage($v['car_type']);
                $list[$k]['current_car_type'] = CarType::getMessage($v['current_car_type']);
                $list[$k]['pass_type'] = PassType::getMessage($v['pass_type']);
                $list[$k]['current_node_name'] = $nodes[$v['current_node_id']]['name'];
                $list[$k]['money'] = round_dollar($v['money']);
                foreach ($list[$k]['correction_record'] as $kk => $vv) {
                    $list[$k]['correction_record'][$kk] = implode('<br/>', $corrections[$vv]['mark']);
                }
                $list[$k]['correction_record'] = implode('<hr>', $list[$k]['correction_record']);
            }
        }
        return success([
            'total' => $count,
            'list' => $list
        ]);
    }

}
