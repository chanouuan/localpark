<?php
/**
 * 异常车
 */

namespace app\pdo;

use app\common\CarType;
use app\common\SignalType;
use app\common\PassType;
use app\common\BroadcastType;
use app\common\AbnormalCarPassWay;

class AbnormalCar extends SuperCar
{

    public function entry (array $post, array $node, array $paths, array $carPaths)
    {
        // 1 纠错失败
        // 2 路径错误
        // 异常车通行方式
        if ($node['abnormal_car_pass_way'] == AbnormalCarPassWay::AUTO_PASS) {
            // 自动放行
            $money = 0;
            $signalType = SignalType::PASS_SUCCESS;
            $broadcastType = BroadcastType::CAR_OUT;
            $passType = PassType::ABNORMAL_PASS;
        } else if ($node['abnormal_car_pass_way'] == AbnormalCarPassWay::CHARGE) {
            // 异常收费
            $money = $node['abnormal_car_charge'];
            if ($money <= 0) {
                $signalType = SignalType::PASS_SUCCESS;
                $broadcastType = BroadcastType::CAR_OUT;
                $passType = PassType::ABNORMAL_PASS;
            } else {
                $signalType = SignalType::CONFIRM_ABNORMAL_CANCEL;
                $broadcastType = BroadcastType::CAR_PAY_OUT;
                $passType = PassType::WAIT_PASS;
            }
        } else if ($node['abnormal_car_pass_way'] == AbnormalCarPassWay::MANUAL_PASS) {
            // 手动放行
            $money = 0;
            $signalType = SignalType::CONFIRM_ABNORMAL_CANCEL;
            $broadcastType = BroadcastType::ABNORMAL_PASS;
            $passType = PassType::WAIT_PASS;
        } else {
            return error(CarType::getMessage(CarType::ABNORMAL_CAR) . '不能通行');
        }

        // 播报消息
        $content = BroadcastType::getContent($broadcastType, [
            'car_number' => $post['car_number'],
            'car_type'   => CarType::getMessage(CarType::ABNORMAL_CAR),
            'money'      => round_dollar($money)
        ]);
        $message   = $content['display'];
        $broadcast = $content['voice'];

        return success([
            'car_type'    => CarType::ABNORMAL_CAR,
            'message'     => $message,
            'broadcast'   => $broadcast,
            'signal_type' => $signalType,
            'pass_type'   => $passType,
            'money'       => $money
        ]);
    }

    public function abnormalPass ($entry, $node_id)
    {
        $nodeModel = new \app\models\NodeModel();
        if (!$nodeInfo = $nodeModel->getNode($node_id)) {
            return error('该节点不存在');
        }
        $money = 0;
        if ($nodeInfo['abnormal_car_pass_way'] == AbnormalCarPassWay::CHARGE) {
            $money = $nodeInfo['abnormal_car_charge'] < 0 ? 0 : $nodeInfo['abnormal_car_charge'];
        }

        // 播报消息
        $content = BroadcastType::getContent(BroadcastType::CAR_OUT, [
            'car_number' => $entry['car_number'],
            'car_type'   => CarType::getMessage(CarType::ABNORMAL_CAR),
            'money'      => round_dollar($money)
        ]);
        $message   = $content['display'];
        $broadcast = $content['voice'];

        return success([
            'car_type'    => CarType::ABNORMAL_CAR,
            'message'     => $message,
            'broadcast'   => $broadcast,
            'pass_type'   => PassType::ABNORMAL_PASS,
            'signal_type' => SignalType::PASS_SUCCESS,
            'money'       => $money
        ]);
    }

    public function revokePass (array $entry, $node_id)
    {
        // 播报消息
        $content = BroadcastType::getContent(BroadcastType::REVOKE_PASS, [
            'car_number' => $entry['car_number'],
            'car_type'   => CarType::getMessage($entry['current_car_type'])
        ]);
        $message   = $content['display'];
        $broadcast = $content['voice'];

        return success([
            'car_type'    => CarType::ABNORMAL_CAR,
            'message'     => $message,
            'broadcast'   => $broadcast,
            'pass_type'   => PassType::REVOKE_PASS,
            'signal_type' => SignalType::NONE
        ]);
    }

}
