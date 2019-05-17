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
            'car_type'    => CarType::ABNORMAL_CAR,
            'message'     => $message,
            'broadcast'   => $broadcast,
            'signal_type' => $signalType,
            'pass_type'   => PassType::ABNORMAL_PASS,
            'money'       => $money
        ]);
    }

    public function abnormalPass ($node_id)
    {
        $nodeModel = new \app\models\NodeModel();
        if (!$nodeInfo = $nodeModel->getNode($node_id)) {
            return error('该节点不存在');
        }
        $money = 0;
        if ($nodeInfo['abnormal_car_pass_way'] == AbnormalCarPassWay::CHARGE) {
            $money = $nodeInfo['abnormal_car_charge'] < 0 ? 0 : $nodeInfo['abnormal_car_charge'];
        }

        return success([
            'car_type'    => CarType::ABNORMAL_CAR,
            'message'     => '一路顺风',
            'broadcast'   => '一路顺风',
            'pass_type'   => PassType::ABNORMAL_PASS,
            'signal_type' => SignalType::PASS_SUCCESS,
            'money'       => $money
        ]);
    }

    public function revokePass (array $entry, $node_id)
    {
        return success([
            'car_type'    => CarType::ABNORMAL_CAR,
            'message'     => '撤销放行',
            'broadcast'   => '撤销放行',
            'pass_type'   => PassType::REVOKE_PASS,
            'signal_type' => SignalType::NONE
        ]);
    }

}
