<?php
/**
 * 会员车
 */

namespace app\pdo;

use app\common\CarType;
use app\common\SignalType;
use app\models\CarModel;

class MemberCar implements SuperCar
{
    /**
     * 入场
     */
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
        $carType = null;
        $message = [];
        $broadcast = [];
        foreach ($carPaths as $k => $v) {
            $memberCarInfo = $memberCars[$v['car_id']];
            if (!isset($memberCarInfo)) {
                continue;
            }
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
                        $allowPass = true;
                        $passIdentity = CarType::CHILD_CAR; // 入场为附属车
                        break;
                    } else {
                        $errorMessage[] = '此' . CarType::getMessage(CarType::CHILD_CAR) . '车位已满';
                        // 子母车位满后是否允许入场
                        if ($paths[$v['path_id']]['allow_child_car']) {
                            $allowPass = true;
                            $passIdentity = CarType::PAY_CAR; // 入场为缴费车 注：出场必缴费
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
