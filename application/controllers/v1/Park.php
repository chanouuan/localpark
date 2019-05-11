<?php

namespace app\controllers;

use ActionPDO;
use app\models\ParkModel;

/**
 * 线下停车场接口
 * @Date 2019-04-07
 */
class Park extends ActionPDO {

    public function __ratelimit ()
    {
        return [
            'pass' => [
                'url' => getgpc('node_id'),
                'interval' => 1000
            ]
        ];
    }

    /**
     * 车辆进出场
     * @param *car_number 车牌号
     * @param *node_id 通道ID
     * @return array
     * {
     * "errNo":0, // 错误码 0成功 -1失败
     * "message":"", //错误消息
     * "result":{
     * }}
     */
    public function pass ()
    {
        return (new ParkModel())->pass(only('car_number', 'node_id'));
    }

}
