<?php
/**
 * 临时车
 */

namespace app\pdo;

use app\common\CarType;
use app\common\SignalType;
use app\common\PassType;

class TempCar extends SuperCar
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
            'car_type'    => $carType,
            'message'     => $message,
            'broadcast'   => $broadcast,
            'signal_type' => $signalType,
            'pass_type'   => $passType
        ]);
    }

    public function out (array $entry, array $parameter, array $paths, array $carPaths)
    {
        $pathId = null;
        $money  = null;
        $code   = null;
        // 查找最便宜的一条路
        foreach ($paths as $k => $v) {
            if (false !== ($load = $this->calculationCode($parameter, $v['calculation_code']))) {
                if (empty($pathId) || $money > $load['cost']) {
                    $pathId = $v['path_id'];
                    $money  = $load['cost'];
                    $code   = $load['code'];
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
            $passType   = PassType::NORMAL_PASS;
        } else {
            $signalType = SignalType::CONFIRM_NORMAL_CANCEL;
            $passType   = PassType::WAIT_PASS;
        }

        if ($signalType == SignalType::PASS_SUCCESS) {
            $message = '一路顺风';
            $broadcast = '一路顺风';
        } else {
            $message = '请缴费' . round_dollar($money) . '元';
            $broadcast = '请缴费' . round_dollar($money) . '元';
        }

        return success([
            'car_type'    => CarType::TEMP_CAR,
            'message'     => $message,
            'broadcast'   => $broadcast,
            'signal_type' => $signalType,
            'pass_type'   => $passType,
            'money'       => $money,
            'code'        => $code,
            'path_id'     => $pathId
        ]);
    }

    public function mid (array $entry, array $node)
    {
        $carType = CarType::TEMP_CAR;

        // 临时车车位数限制
        if ($node['temp_car_count'] > 0 && $node['temp_car_left'] <= 0) {
            return error(CarType::getMessage($carType) . '车位已满');
        }

        // 消息
        $message = '欢迎光临';
        $broadcast = '欢迎光临';

        return success([
            'car_type'    => $carType,
            'message'     => $message,
            'broadcast'   => $broadcast,
            'signal_type' => SignalType::PASS_SUCCESS,
            'pass_type'   => PassType::NORMAL_PASS
        ]);
    }

    public function normalPass (array $entry)
    {
        return success([
            'car_type'    => CarType::TEMP_CAR,
            'message'     => '一路顺风',
            'broadcast'   => '一路顺风',
            'pass_type'   => PassType::NORMAL_PASS,
            'signal_type' => SignalType::PASS_SUCCESS
        ]);
    }

    public function revokePass (array $entry, $node_id)
    {
        return success([
            'car_type'    => CarType::TEMP_CAR,
            'message'     => '撤销放行',
            'broadcast'   => '撤销放行',
            'pass_type'   => PassType::REVOKE_PASS,
            'signal_type' => SignalType::NONE
        ]);
    }

}
