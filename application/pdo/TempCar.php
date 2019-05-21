<?php
/**
 * 临时车
 */

namespace app\pdo;

use app\common\CarType;
use app\common\SignalType;
use app\common\PassType;
use app\common\BroadcastType;

class TempCar extends SuperCar
{

    public function entry (array $post, array $node, array $paths, array $carPaths)
    {
        // 临时车车位数限制
        if ($node['temp_car_count'] > 0 && $node['temp_car_left'] <= 0) {
            $broadcastType = BroadcastType::PLACE_LIMIT_ENTRY;
            $signalType    = SignalType::CONFIRM_ABNORMAL_CANCEL;
        } else {
            $broadcastType = BroadcastType::CAR_ENTRY;
            $signalType    = SignalType::PASS_SUCCESS;
        }

        // 播报消息
        $content = BroadcastType::getContent($broadcastType, [
            'car_number' => $post['car_number'],
            'car_type'   => CarType::getMessage(CarType::TEMP_CAR),
            'rest'       => $node['temp_car_left']
        ]);
        $message   = $content['display'];
        $broadcast = $content['voice'];

        // 通行方式
        if ($signalType == SignalType::PASS_SUCCESS) {
            $passType = PassType::NORMAL_PASS;
        } else {
            $passType = PassType::WAIT_PASS;
        }

        return success([
            'car_type'    => CarType::TEMP_CAR,
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
            $signalType    = SignalType::PASS_SUCCESS;
            $passType      = PassType::NORMAL_PASS;
            $broadcastType = BroadcastType::CAR_OUT;
        } else {
            $signalType    = SignalType::CONFIRM_NORMAL_CANCEL;
            $passType      = PassType::WAIT_PASS;
            $broadcastType = BroadcastType::CAR_PAY_OUT;
        }

        // 播报消息
        $content = BroadcastType::getContent($broadcastType, [
            'car_number' => $entry['car_number'],
            'car_type'   => CarType::getMessage(CarType::TEMP_CAR),
            'money'      => round_dollar($money)
        ]);
        $message   = $content['display'];
        $broadcast = $content['voice'];

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
        // 临时车车位数限制
        if ($node['temp_car_count'] > 0 && $node['temp_car_left'] <= 0) {
            $broadcastType = BroadcastType::PLACE_LIMIT_ENTRY;
        } else {
            $broadcastType = BroadcastType::CAR_ENTRY;
        }

        // 播报消息
        $content = BroadcastType::getContent($broadcastType, [
            'car_number' => $entry['car_number'],
            'car_type'   => CarType::getMessage(CarType::TEMP_CAR),
            'rest'       => $node['temp_car_left']
        ]);
        $message   = $content['display'];
        $broadcast = $content['voice'];

        // 中场临时车直接返回错误信息
        if ($broadcastType == BroadcastType::PLACE_LIMIT_ENTRY) {
            return error($broadcast);
        }

        return success([
            'car_type'    => CarType::TEMP_CAR,
            'message'     => $message,
            'broadcast'   => $broadcast,
            'signal_type' => SignalType::PASS_SUCCESS,
            'pass_type'   => PassType::NORMAL_PASS
        ]);
    }

    public function normalPass (array $entry)
    {
        // 播报消息
        $content = BroadcastType::getContent(BroadcastType::CAR_OUT, [
            'car_number' => $entry['car_number'],
            'car_type'   => CarType::getMessage(CarType::TEMP_CAR),
            'money'      => round_dollar($entry['money'])
        ]);
        $message   = $content['display'];
        $broadcast = $content['voice'];

        return success([
            'car_type'    => CarType::TEMP_CAR,
            'message'     => $message,
            'broadcast'   => $broadcast,
            'pass_type'   => PassType::NORMAL_PASS,
            'signal_type' => SignalType::PASS_SUCCESS
        ]);
    }

    public function revokePass (array $entry, $node_id)
    {
        // 播报消息
        $content = BroadcastType::getContent(BroadcastType::REVOKE_PASS, [
            'car_number' => $entry['car_number'],
            'car_type'   => CarType::getMessage(CarType::TEMP_CAR)
        ]);
        $message   = $content['display'];
        $broadcast = $content['voice'];

        return success([
            'car_type'    => CarType::TEMP_CAR,
            'message'     => $message,
            'broadcast'   => $broadcast,
            'pass_type'   => PassType::REVOKE_PASS,
            'signal_type' => SignalType::NONE
        ]);
    }

}
