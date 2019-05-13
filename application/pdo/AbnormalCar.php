<?php
/**
 * 异常车
 */

namespace app\pdo;

use app\common\CarType;
use app\common\SignalType;
use app\common\PassType;

class AbnormalCar extends SuperCar
{

    public function entry (array $node, array $paths, array $carPaths)
    {
        // 1 纠错失败
        // 2 路径错误
        // 异常车通行方式
        if ($node['abnormal_car_pass_way'] == AbnormalCarPassWay::AUTO_PASS) {
            // 自动放行
            $money = 0;
            $signalType = SignalType::PASS_SUCCESS;
            $message = '一路顺风';
            $broadcast = '一路顺风';
        } else if ($node['abnormal_car_pass_way'] == AbnormalCarPassWay::CHARGE) {
            // 异常收费
            $money = $node['abnormal_car_charge'];
            if ($money <= 0) {
                $signalType = SignalType::PASS_SUCCESS;
            } else {
                $signalType = SignalType::CONFIRM_ABNORMAL_CANCEL;
            }
            $message = '请缴费' . round_dollar($money) . '元';
            $broadcast = '请缴费' . round_dollar($money) . '元';
        } else if ($node['abnormal_car_pass_way'] == AbnormalCarPassWay::MANUAL_PASS) {
            // 手动放行
            $money = 0;
            $signalType = SignalType::CONFIRM_ABNORMAL_CANCEL;
            $message = '异常车通行';
            $broadcast = '异常车通行';
        } else {
            return error(CarType::getMessage(CarType::ABNORMAL_CAR) . '不能通行');
        }

        return success([
            'carType' => CarType::ABNORMAL_CAR,
            'message' => $message,
            'broadcast' => $broadcast,
            'signalType' => $signalType,
            'passType' => PassType::ABNORMAL_PASS,
            'money' => $money
        ]);
    }

}
