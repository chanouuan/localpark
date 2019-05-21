<?php
/**
 * 会员车
 */

namespace app\pdo;

use app\common\CarType;
use app\common\SignalType;
use app\common\PassType;
use app\common\BroadcastType;
use app\models\CarModel;

class MemberCar extends SuperCar
{

    public function entry (array $post, array $node, array $paths, array $carPaths)
    {
        // 去掉无效路径
        $paths = array_column($paths, null, 'id');
        foreach ($carPaths as $k => $v) {
            if (!isset($paths[$v['path_id']])) {
                unset($carPaths[$k]);
            }
        }

        // 会员车状态
        $memberCars = (new CarModel())->validationMemberCarType(array_column($carPaths, 'car_id'));
        $memberCars = array_column($memberCars, null, 'id');

        // 多条路径，若有一条路径成立，就通行
        $signalType = null;
        $carType    = null;
        $carId      = null;
        $message    = [];
        $broadcast  = [];
        foreach ($carPaths as $k => $v) {
            $memberCarInfo = $memberCars[$v['car_id']];
            if (!isset($memberCarInfo)) {
                continue;
            }
            $pathInfo = $paths[$v['path_id']];
            // 会员车是否失效
            if (!$memberCarInfo['available']) {
                // 播报消息
                $content = BroadcastType::getContent(BroadcastType::MEMBER_CAR_INVALID_ENTRY, [
                    'car_number' => $post['car_number'],
                    'car_type'   => CarType::getMessage($memberCarInfo['car_type'])
                ]);
                $message[]   = $content['display'];
                $broadcast[] = $content['voice'];
                // 失效会员车是否允许入场
                if ($pathInfo['allow_invalid_car']) {
                    $carId      = $v['car_id'];
                    $signalType = SignalType::PASS_SUCCESS;
                    $carType    = CarType::INVALID_CAR; // 入场为过期车 注：会员车失效后是过期车，不会变成临时车
                }
            } else {
                // 子母车位数限制
                if (count($v['car_number']) > 1) {
                    // 剩余车位数
                    if ($v['place_left'] > 0) {
                        // 播报消息
                        $broadcastType = BroadcastType::CAR_ENTRY;
                        if (CarType::isTimeCar($memberCarInfo['car_type'])) {
                            $broadcastType = BroadcastType::MEMBER_CAR_DAY_ENTRY;
                        }
                        $content = BroadcastType::getContent($broadcastType, [
                            'car_number' => $post['car_number'],
                            'car_type'   => CarType::getMessage($memberCarInfo['car_type']),
                            'day'        => CarType::isTimeCar($memberCarInfo['car_type']) && $memberCarInfo['available'] ? round((strtotime($memberCarInfo['end_time']) - TIMESTAMP) / 86400) : 0
                        ]);
                        $message    = [$content['display']];
                        $broadcast  = [$content['voice']];
                        $signalType = SignalType::PASS_SUCCESS;
                        $carType    = CarType::CHILD_CAR; // 入场为附属车
                        $carId      = $v['car_id'];
                        break;
                    } else {
                        // 播报消息
                        $content = BroadcastType::getContent(BroadcastType::PLACE_LIMIT_ENTRY, [
                            'car_number' => $post['car_number'],
                            'car_type'   => CarType::getMessage(CarType::CHILD_CAR),
                            'rest'       => $v['place_left']
                        ]);
                        $message[]   = $content['display'];
                        $broadcast[] = $content['voice'];
                        // 子母车位满后是否允许入场
                        if ($pathInfo['allow_child_car']) {
                            $signalType = SignalType::PASS_SUCCESS;
                            $carType    = CarType::PAY_CAR; // 入场为缴费车 注：出场必缴费
                            $carId      = $v['car_id'];
                        }
                    }
                } else {
                    $signalType = SignalType::PASS_SUCCESS;
                    $carType    = $memberCarInfo['car_type']; // 入场为会员车
                    // 播报消息
                    $broadcastType = BroadcastType::CAR_ENTRY;
                    if (CarType::isTimeCar($carType)) {
                        $broadcastType = BroadcastType::MEMBER_CAR_DAY_ENTRY;
                    }
                    $content = BroadcastType::getContent($broadcastType, [
                        'car_number' => $post['car_number'],
                        'car_type'   => CarType::getMessage($carType),
                        'day'        => CarType::isTimeCar($memberCarInfo['car_type']) && $memberCarInfo['available'] ? round((strtotime($memberCarInfo['end_time']) - TIMESTAMP) / 86400) : 0
                    ]);
                    $message   = [$content['display']];
                    $broadcast = [$content['voice']];
                    $carId     = $v['car_id'];
                    break;
                }
            }
        }

        if (empty($message)) {
            return error('此会员车不能入场');
        }

        // 通行方式
        if ($signalType == SignalType::PASS_SUCCESS) {
            $passType = PassType::NORMAL_PASS;
        } else {
            $signalType = SignalType::CONFIRM_ABNORMAL_CANCEL;
            $passType = PassType::WAIT_PASS;
        }

        return success([
            'car_type'    => $carType,
            'car_id'      => $carId,
            'message'     => implode(',', $message),
            'broadcast'   => implode(',', $broadcast),
            'signal_type' => $signalType,
            'pass_type'   => $passType
        ]);
    }

    public function out (array $entry, array $parameter, array $paths, array $carPaths)
    {
        // 去掉无效路径
        $paths = array_column($paths, null, 'id');
        foreach ($carPaths as $k => $v) {
            if (!isset($paths[$v['path_id']])) {
                unset($carPaths[$k]);
            }
        }

        $carModel = new CarModel();

        // 会员车状态
        $memberCars = $carModel->validationMemberCarType(array_column($carPaths, 'car_id'));
        $memberCars = array_column($memberCars, null, 'id');

        $pathId  = null;
        $money   = null;
        $code    = null;
        $carInfo = null;
        $carType = null;
        // 查找最便宜的一条路
        foreach ($carPaths as $k => $v) {
            $memberCarInfo = $memberCars[$v['car_id']];
            if (!isset($memberCarInfo)) {
                continue;
            }
            $pathInfo = $paths[$v['path_id']];
            $memberCarType = $memberCarInfo['car_type'];
            if (!$memberCarInfo['available']) {
                $memberCarType = CarType::INVALID_CAR;
            } else {
                if (count($v['car_number']) > 1) {
                    $memberCarType = $entry['last_nodes'][0]['car_type'] == CarType::PAY_CAR ? CarType::PAY_CAR : CarType::CHILD_CAR;
                }
            }
            $parameter['车辆类型'] = CarType::getMessage($memberCarInfo['car_type']);
            $parameter['available'] = $memberCarType == CarType::PAY_CAR ? false : $memberCarInfo['available']; // 注意判断缴费车
            $parameter['余额'] = $memberCarInfo['balance'];
            if (false !== ($load = $this->calculationCode($parameter, $pathInfo['calculation_code']))) {
                if (empty($pathId) || $money > $load['cost']) {
                    $pathId  = $v['path_id'];
                    $money   = $load['cost'];
                    $code    = $load['code'];
                    $carInfo = $memberCarInfo;
                    $carType = $memberCarType;
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

        // 播报消息类型
        if ($money === 0) {
            $broadcastType = BroadcastType::CAR_OUT;
        } else {
            $broadcastType = BroadcastType::CAR_PAY_OUT;
        }

        if ($carInfo['car_type'] == CarType::STORE_CARD_CAR) {
            // 储值卡车
            if ($money > 0) {
                if ($money > $carInfo['balance']) {
                    $money = $money - $carInfo['balance'];
                    $broadcastType = BroadcastType::STORE_CARD_CAR_OUT;
                } else {
                    // 扣费
                    if (!$carModel->storeCardCarChangeBalance($carInfo['id'], $entry['car_number'], $money, $entry['id'])) {
                        return error('此储值卡车扣费失败');
                    }
                    // 已扣完费
                    $money = 0;
                    $broadcastType = BroadcastType::CAR_OUT;
                }
            }
        } else if (CarType::isTimeCar($carInfo['car_type'])) {
            // 过期车
            if ($money > 0) {
                $broadcastType = BroadcastType::MEMBER_CAR_INVALID_OUT;
            }
        }

        // 通行方式
        if ($money === 0) {
            $signalType = SignalType::PASS_SUCCESS;
            $passType   = PassType::NORMAL_PASS;
        } else {
            $signalType = SignalType::CONFIRM_NORMAL_CANCEL;
            $passType   = PassType::WAIT_PASS;
        }

        // 播报消息
        $content = BroadcastType::getContent($broadcastType, [
            'car_number' => $entry['car_number'],
            'car_type'   => CarType::getMessage($carType),
            'money'      => round_dollar($money)
        ]);
        $message   = $content['display'];
        $broadcast = $content['voice'];

        return success([
            'car_type'    => $carType,
            'car_id'      => $carInfo['id'],
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
        $carType = CarType::isMemberCar($entry['current_car_type']) ? $entry['current_car_type'] : CarType::MEMBER_CAR;

        // 播报消息
        $content = BroadcastType::getContent(BroadcastType::CAR_ENTRY, [
            'car_number' => $entry['car_number'],
            'car_type'   => CarType::getMessage($carType)
        ]);
        $message   = $content['display'];
        $broadcast = $content['voice'];

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
        // 储值卡车
        if ($entry['current_car_type'] == CarType::STORE_CARD_CAR) {
            $carModel = new CarModel();
            if (!$carInfo = $carModel->find([
                'id' => $entry['car_id'], 'car_type' => CarType::STORE_CARD_CAR
            ], 'balance')) {
                return error('此储值卡车无效');
            }
            // 扣费
            if (!$carModel->storeCardCarChangeBalance($entry['car_id'], $entry['car_number'], $carInfo['balance'], $entry['id'])) {
                return error('此储值卡车扣费失败');
            }
        }

        // 播报消息
        $content = BroadcastType::getContent(BroadcastType::CAR_OUT, [
            'car_number' => $entry['car_number'],
            'car_type'   => CarType::getMessage($entry['current_car_type']),
            'money'      => round_dollar($entry['money'])
        ]);
        $message   = $content['display'];
        $broadcast = $content['voice'];

        return success([
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
            'car_type'   => CarType::getMessage($entry['current_car_type'])
        ]);
        $message   = $content['display'];
        $broadcast = $content['voice'];

        return success([
            'car_type'    => CarType::MEMBER_CAR,
            'message'     => $message,
            'broadcast'   => $broadcast,
            'pass_type'   => PassType::REVOKE_PASS,
            'signal_type' => SignalType::NONE
        ]);
    }
}
