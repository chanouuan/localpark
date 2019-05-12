<?php
/**
 * 临时车
 */

namespace app\pdo;

use app\common\CarType;
use app\common\SignalType;

class TempCar implements SuperCar
{
    /**
     * 入场
     */
    public function entry (array $node, array $paths, array $carPaths)
    {
        $carType = CarType::TEMP_CAR;
        if ($node['temp_car_count'] > 0 && $node['temp_car_left'] <= 0) {
            $message = CarType::getMessage($carType) . '车位已满';
            $broadcast = CarType::getMessage($carType) . '车位已满';
            $signalType = SignalType::CONFIRM_ABNORMAL_CANCEL;
        } else {
            $message = '欢迎光临';
            $broadcast = '欢迎光临';
            $signalType = SignalType::PASS_SUCCESS;
        }
        return success([
            'carType' => $carType, 'message' => $message, 'broadcast' => $broadcast, 'signalType' => $signalType
        ]);
    }

    public function out ()
    {

    }

    public function mid ()
    {

    }
}
