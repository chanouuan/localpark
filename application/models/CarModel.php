<?php

namespace app\models;

use Crud;
use app\common\CarType;

class CarModel extends Crud {

    protected $table = 'chemi_car';

    /**
     * 更新车辆信息
     * @return bool
     */
    public function saveCar (array $data, array $condition)
    {
        return $this->getDb()->update($this->table, $data, $condition);
    }

    /**
     * 储值卡车扣费
     * @param $car_id
     * @param $car_number
     * @param $cost
     * @param $pass_id
     * @return bool
     */
    public function storeCardCarChangeBalance ($car_id, $car_number, $cost, $pass_id)
    {
        // 重复起竿 (起竿后车辆不通过)，会造成重复扣费
        $tradeInfo = $this->getDb()->table('chemi_car_trade')->field('money')->where(['pass_id' => $pass_id, 'car_id' => $car_id])->order('id desc')->limit(1)->find();
        if ($tradeInfo) {
            if ($tradeInfo['money'] == $cost) {
                return true;
            }
        }
        // 扣除余额
        if (!$this->saveCar(['balance' => ['balance-' . $cost]], ['id' => $car_id])) {
            return false;
        }
        // 记录变动
        return $this->saveCarTrade([
            'title' => '储值卡车扣费',
            'car_id' => $car_id,
            'car_number' => $car_number,
            'pass_id' => $pass_id,
            'money' => $cost
        ]);
    }

    /**
     * 保存计费记录
     * @param $data
     * @return bool
     */
    public function saveCarTrade ($data)
    {
        $data['mark'] = '-';
        $data['create_time'] = date('Y-m-d H:i:s', TIMESTAMP);
        return $this->getDb()->insert('chemi_car_trade', $data);
    }

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
        $list = $this->getDb()
            ->table('chemi_car car left join chemi_entry entry on entry.car_id = car.id')
            ->field('car.car_number')
            ->where('car.status = 1 and entry.id is null')->select();
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
