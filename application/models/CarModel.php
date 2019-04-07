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
            ->field('id,car_id,path_id,car_number,place_count,place_recount')
            ->where([
                'JSON_CONTAINS(car_number,\'"' . $car_number . '"\')',
                'status = 1'
            ])
            ->select();
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
        };
        return [
            'car_type' => CarType::MEMBER_CAR,
            'car_path' => $carPaths,
            'paths' => (new PathModel())->getPathNodeById(array_column($carPaths, 'path_id'))
        ];
    }
}