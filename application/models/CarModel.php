<?php

namespace app\models;

use Crud;
use app\common\CarType;

class CarModel extends Crud {

    protected $table = 'chemi_car';

    /**
     * 查询车牌号对应的路径
     * @param $car_number
     * @return array
     */
    public function queryCarPaths ($car_number)
    {
        $list = $this->getDb()
            ->table('chemi_car_path')
            ->field('id,car_id,path_id,car_number,place_count,place_left')
            ->where([
                'JSON_CONTAINS(car_number,\'"' . $car_number . '"\')',
                'status = 1'
            ])
            ->select();
        if ($list) {
            foreach ($list as $k => $v) {
                $list[$k]['car_number'] = json_decode($v['car_number'], true);
            }
        }
        return $list;
    }

    /**
     * 获取会员车信息
     * @param $car_number
     * @return array
     */
    public function getCarType ($car_number)
    {
        if (!$carPaths = $this->queryCarPaths($car_number)) {
            return [];
        }
        return [
            'car_type' => CarType::MEMBER_CAR,
            'car_path' => $carPaths,
            'paths' => (new PathModel())->getPathNodeById(array_column($carPaths, 'path_id'))
        ];
    }

    /**
     * 验证会员车类型是否有效
     * @param $car_id
     * @return array
     */
    public function validationMemberCarType (array $car_id)
    {
        $list = $this->select(['id' => ['in', $car_id], 'status' => 1], 'id,car_type,start_time,end_time,balance');
        if ($list) {
            foreach ($list as $k => $v) {
                if ($v['car_type'] == CarType::MONTH_CARD_CAR) {
                    // 月卡车
                    $list[$k]['available'] = TIMESTAMP > strtotime($v['start_time']) && TIMESTAMP < strtotime($v['end_time']);
                } else if ($v['car_type'] == CarType::VIP_CAR) {
                    // 贵宾车
                    $list[$k]['available'] = TIMESTAMP < strtotime($v['end_time']);
                } else if ($v['car_type'] == CarType::FIXED_CAR) {
                    // 固定车
                    $list[$k]['available'] = TIMESTAMP < strtotime($v['end_time']);
                } else if ($v['car_type'] == CarType::STORE_CARD_CAR) {
                    // 储值卡车
                    $list[$k]['available'] = $v['balance'] > 0;
                } else if ($v['car_type'] == CarType::ORDINARY_CAR) {
                    // 普通车
                    $list[$k]['available'] = true;
                }
            }
        }
        return $list;
    }

    /**
     * 获取所有不在场的会员车牌
     * @return array
     */
    public function getAllNoEntryCarNumber ()
    {
        $list = $this->select(['is_entry' => 0], 'car_number');
        if ($list) {
            $list = array_column($list, 'car_number');
        }
        return $list;
    }

    /**
     * 获取上次出场时间
     * @param $car_number 车牌号
     * @return date
     */
    public function getLastOutParkTime ($car_number)
    {
        $carInfo = $this->find(['car_number' => $car_number], 'out_time');
        return $carInfo['out_time'] ? strtotime($carInfo['out_time']) : 0;
    }
}
