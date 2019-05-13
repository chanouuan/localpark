<?php
/**
 * 临时车
 */

namespace app\pdo;

use app\common\CarType;
use app\common\SignalType;
use app\common\PassType;

class TempCar implements SuperCar
{

    public function entry (array $node, array $paths, array $carPaths)
    {
        $carType = CarType::TEMP_CAR;

        // 临时车车位数限制
        if ($node['temp_car_count'] > 0 && $node['temp_car_left'] <= 0) {
            $message = CarType::getMessage($carType) . '车位已满';
            $broadcast = CarType::getMessage($carType) . '车位已满';
            $signalType = SignalType::CONFIRM_ABNORMAL_CANCEL;
        } else {
            $message = '欢迎光临';
            $broadcast = '欢迎光临';
            $signalType = SignalType::PASS_SUCCESS;
        }

        // 通行方式
        if ($signalType == SignalType::PASS_SUCCESS) {
            $passType = PassType::NORMAL_PASS;
        } else {
            $passType = PassType::WAIT_PASS;
        }

        return success([
            'carType' => $carType,
            'message' => $message,
            'broadcast' => $broadcast,
            'signalType' => $signalType,
            'passType' => $passType
        ]);
    }

    public function out (array $parameter, array $paths, array $carPaths)
    {
        $carType = CarType::TEMP_CAR;

        $pathId = null;
        $money = null;
        // 查找最便宜的一条路
        foreach ($paths as $k => $v) {
            if (false !== ($calculationMoney = $this->calculationCode($parameter, $v['calculation_code']))) {
                if (empty($pathId) || $money > $calculationMoney) {
                    $pathId = $v['id'];
                    $money = $calculationMoney;
                    if ($money === 0) {
                        break;
                    }
                }
            }
        }

        // 结算金额异常
        if (empty($pathId)) {
            return error('结算金额异常');
        }

        // 通行方式
        if ($money === 0) {
            $signalType = SignalType::PASS_SUCCESS;
            $passType = PassType::NORMAL_PASS;
        } else {
            $signalType = SignalType::CONFIRM_NORMAL_CANCEL;
            $passType = PassType::WAIT_PASS;
        }

        return success([
            'carType' => $carType,
            'message' => $message,
            'broadcast' => $broadcast,
            'signalType' => $signalType,
            'passType' => $passType
        ]);
    }

    public function mid ()
    {

    }
}
