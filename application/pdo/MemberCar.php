<?php
/**
 * 会员车
 */

namespace app\pdo;

use app\common\CarType;
use app\common\SignalType;
use app\models\CarModel;

class MemberCar extends SuperCar
{

    public function entry (array $node, array $paths, array $carPaths)
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
            $carId = $v['car_id'];
            $signalType = SignalType::CONFIRM_ABNORMAL_CANCEL;
            $carType = $memberCarInfo['car_type'];
            // 会员车是否失效
            if (!$memberCarInfo['available']) {
                $message[] = '此' . CarType::getMessage($memberCarInfo['car_type']) . '已失效';
                $broadcast[] = '此' . CarType::getMessage($memberCarInfo['car_type']) . '已失效';
                // 失效会员车是否允许入场
                if ($paths[$v['path_id']]['allow_invalid_car']) {
                    $signalType = SignalType::PASS_SUCCESS;
                    $carType = CarType::INVALID_CAR; // 入场为过期车 注：会员车失效后是过期车，不会变成临时车
                }
            } else {
                // 子母车位数限制
                if (count($v['car_number']) > 1) {
                    // 剩余车位数
                    if ($v['place_left'] > 0) {
                        $signalType = SignalType::PASS_SUCCESS;
                        $carType = CarType::CHILD_CAR; // 入场为附属车
                        $message[] = '欢迎光临';
                        $broadcast[] = '欢迎光临';
                        break;
                    } else {
                        $message[] = '此' . CarType::getMessage(CarType::CHILD_CAR) . '车位已满';
                        $broadcast[] = '此' . CarType::getMessage(CarType::CHILD_CAR) . '车位已满';
                        // 子母车位满后是否允许入场
                        if ($paths[$v['path_id']]['allow_child_car']) {
                            $signalType = SignalType::PASS_SUCCESS;
                            $carType = CarType::PAY_CAR; // 入场为缴费车 注：出场必缴费
                        }
                    }
                } else {
                    $signalType = SignalType::PASS_SUCCESS;
                    $carType = $memberCarInfo['car_type']; // 入场为会员车
                    $message[] = '欢迎光临';
                    $broadcast[] = '欢迎光临';
                    break;
                }
            }
        }

        if (empty($carType)) {
            return error('此会员车不能入场');
        }

        // 通行方式
        if ($signalType == SignalType::PASS_SUCCESS) {
            $passType = PassType::NORMAL_PASS;
        } else {
            $passType = PassType::WAIT_PASS;
        }

        return success([
            'carType'    => $carType,
            'carId'      => $carId,
            'message'    => implode('', $message),
            'broadcast'  => implode('', $broadcast),
            'signalType' => $signalType,
            'passType'   => $passType
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

        $pathId = null;
        $money  = null;
        $code   = null;
        $car    = null;
        // 查找最便宜的一条路
        foreach ($carPaths as $k => $v) {
            $memberCarInfo = $memberCars[$v['car_id']];
            if (!isset($memberCarInfo)) {
                continue;
            }
            $pathInfo = $paths[$v['path_id']];
            $parameter['车辆类型'] = CarType::getMessage($memberCarInfo['car_type']);
            $parameter['available'] = $entry['entry_car_type'] == CarType::PAY_CAR ? false : $memberCarInfo['available']; // 注意判断缴费车
            $parameter['余额'] = $memberCarInfo['balance'];
            if (false !== ($load = $this->calculationCode($parameter, $pathInfo['calculation_code']))) {
                if (empty($pathId) || $money > $load['cost']) {
                    $pathId = $v['path_id'];
                    $money  = $load['cost'];
                    $code   = $load['code'];
                    $car    = $memberCarInfo;
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

        // 消息
        $message = '请缴费' . round_dollar($money) . '元';
        $broadcast = '请缴费' . round_dollar($money) . '元';

        // 储值卡车
        if ($car['car_type'] == CarType::STORE_CARD_CAR) {
            if ($money > 0) {
                if ($money > $car['balance']) {
                    $money = $money - $car['balance'];
                    $message = '此卡余额不足,请缴费' . round_dollar($money) . '元';
                    $broadcast = '此卡余额不足,请缴费' . round_dollar($money) . '元';
                } else {
                    // 扣费
                    if (!$carModel->storeCardCarChangeBalance($car['id'], $entry['car_number'], $money, $entry['id'])) {
                        return error('此储值卡车扣费失败');
                    }
                    // 已扣完费
                    $money = 0;
                }
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

        // 消息
        if ($signalType == SignalType::PASS_SUCCESS) {
            $message = '一路顺风';
            $broadcast = '一路顺风';
        }

        return success([
            'carType'    => $car['car_type'],
            'carId'      => $car['id'],
            'message'    => $message,
            'broadcast'  => $broadcast,
            'signalType' => $signalType,
            'passType'   => $passType,
            'money'      => $money,
            'code'       => $code,
            'pathId'     => $pathId
        ]);
    }

    public function mid (array $node)
    {
        // 消息
        $message = '欢迎光临';
        $broadcast = '欢迎光临';

        return success([
            'carType'    => CarType::MEMBER_CAR,
            'message'    => $message,
            'broadcast'  => $broadcast,
            'signalType' => SignalType::PASS_SUCCESS,
            'passType'   => PassType::NORMAL_PASS
        ]);
    }

    public function normalPass (array $entry)
    {
        // 储值卡车
        if ($entry['out_car_type'] == CarType::STORE_CARD_CAR) {
            $carModel = new CarModel();
            if (!$carInfo = $carModel->find([
                'id' => $entry['car_id'], 'car_type' => CarType::STORE_CARD_CAR
            ], 'balance')) {
                return error('此储值卡车无效');
            }
            if (!$carModel->storeCardCarChangeBalance($entry['car_id'], $entry['car_number'], $carInfo['balance'], $entry['id'])) {
                return error('此储值卡车扣费失败');
            }
        }

        return success([
            'message'    => '一路顺风',
            'broadcast'  => '一路顺风',
            'passType'   => PassType::NORMAL_PASS,
            'signalType' => SignalType::PASS_SUCCESS,
        ]);
    }
}
