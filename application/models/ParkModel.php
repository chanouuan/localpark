<?php

namespace app\models;

use Crud;
use app\common\CarType;
use app\common\PassType;
use app\common\SignalType;

class ParkModel extends Crud {

    protected $nodeModel;
    protected $carModel;
    protected $pathModel;
    protected $entryModel;
    protected $outModel;

    public function __construct()
    {
        // 加载 model
        $this->nodeModel  = new NodeModel();
        $this->carModel   = new CarModel();
        $this->pathModel  = new PathModel();
        $this->entryModel = new EntryModel();
        $this->outModel   = new OutModel();
    }

    /**
     * 车辆进出场
     * @param onduty_id 值班员
     * @param node_id 进出场节点
     * @param car_number 车牌号
     * @return array
     */
    public function pass (array $post)
    {
        // 值班员
        $post['onduty_id'] = intval($post['onduty_id']);

        // 节点
        $post['node_id'] = intval($post['node_id']);
        $post['correction_record_id'] = intval($post['correction_record_id']);

        // 车牌号验证
        if (!check_car_license($post['car_number'])) {
            return error('车牌号错误');
        }

        // 验证节点
        if (!$nodeInfo = $this->nodeModel->getNode($post['node_id'])) {
            return error('节点未找到');
        }

        // 查询车辆类型
        $carPaths = $this->getCarType($post['car_number']);

        if (empty($carPaths['paths'])) {
            return error(CarType::getMessage($carPaths['car_type']) . ',不能进场');
        }

        // 获取当前节点是起点的路径
        $startPaths = $this->diffPath($carPaths['paths'], $post['node_id'], 'start_node');
        // 获取当前节点是中点的路径
        $midPaths   = $this->diffPath($carPaths['paths'], $post['node_id']);
        // 获取当前节点是终点的路径
        $endPaths   = $this->diffPath($carPaths['paths'], $post['node_id'], 'end_node');

        // 已纠正过，第二次验证
        if ($post['error_count']) {
            if (empty($startPaths) && empty($midPaths) && empty($endPaths)) {
                return error('此通道禁止通行');
            }
        }

        // 查询在场车辆
        $entryCarInfo = $this->entryModel->getCarInfo($post['car_number']);

        // 判断是否是在场车
        if (empty($entryCarInfo)) {
            if (empty($startPaths)) {
                // 如果不是起点
                // 1.无入场信息
                // 2.车牌识别错
                // todo 车牌纠错
                $correctionResult = $this->correctionLogic($post, ['NO_ENTRY', 'NO_START_NODE']);
                $post = array_merge($post, $correctionResult['result']);
                if ($correctionResult['errorcode'] !== 0) {
                    // 异常车
                    return $this->abnormalCarNumber($post, $nodeInfo, $entryCarInfo);
                }
                return $this->pass($post);
            } else {
                // 如果是起点
                // 判断是否允许入场
                // 1.临时车是否允许入场
                // 2.会员车失效后是否允许入场 (月卡过期、余额不足)
                // 3.多卡多车，附属车位满后是否允许入场
                // 4.有可能以错车牌入场
                // todo 入场
                return $this->entry($post, $nodeInfo, $startPaths, $carPaths);
            }
        }

        // 异常车通行
        if ($entryCarInfo['pass_type'] == PassType::ABNORMAL_PASS) {
            // 只要不是起点，都按异常车通行，因为起点会自动将入场异常车移到出入场记录表
            if (empty($startPaths) || ($endPaths && $startPaths)) {
                return $this->abnormalCarNumber($post, $nodeInfo, $entryCarInfo);
            }
        }

        // 每个节点状态
        // 1.起点 不在场
        // 2.节点 在场
        // 3.终点 在场
        // 4.终点&起点 在场

        // 判断是否终点
        if ($endPaths) {
            // 终点路径验证
            $correctPaths = $this->verifyPath($post['node_id'], $endPaths, $entryCarInfo['last_nodes']);
            if (empty($correctPaths)) {
                // 1.车牌识别错
                // todo 车牌纠错
                $correctionResult = $this->correctionLogic($post, ['ENTRY', 'END_NODE', 'PATH_ERROR']);
                $post = array_merge($post, $correctionResult['result']);
                if ($correctionResult['errorcode'] !== 0) {
                    // 异常车
                    return $this->abnormalCarNumber($post, $nodeInfo, $entryCarInfo);
                }
                return $this->pass($post);
            }
            // todo 出场&计费
            $outResult = $this->out($post, $entryCarInfo, $correctPaths, $carPaths);
            if ($outResult['errorcode'] !== 0) {
                return $outResult;
            }
            if (empty($startPaths) || $outResult['result']['status'] != SignalType::PASS_SUCCESS) {
                // todo 起竿&语音播报
                return $outResult;
            }
            // 出场记录
            if (!$this->outModel->addOutInfo($entryCarInfo['id'])) {
                return error('记录出场错误,请重试');
            }
            // 既是终点又是起点
            return $this->pass([
                'node_id' => $post['node_id'], 'car_number' => $post['car_number'], 'onduty_id' => $post['onduty_id']
            ]);
        }

        // 判断是否起点
        if ($startPaths) {
            // 1.车牌识别错
            // 2.上次出场异常 (系统错误、跟车...)
            // todo 车牌纠错
            $correctionResult = $this->correctionLogic($post, ['ENTRY', 'START_NODE']);
            $post = array_merge($post, $correctionResult['result']);
            if ($correctionResult['errorcode'] !== 0) {
                // 异常车
                return $this->abnormalCarNumber($post, $nodeInfo, $entryCarInfo);
            }
            return $this->pass($post);
        }

        // 中点路径验证
        $correctPaths = $this->verifyPath($post['node_id'], $midPaths, $entryCarInfo['last_nodes']);
        if (empty($correctPaths)) {
            // 1.车牌识别错
            // todo 车牌纠错
            $correctionResult = $this->correctionLogic($post, ['ENTRY', 'MIDDLE_NODE', 'PATH_ERROR']);
            $post = array_merge($post, $correctionResult['result']);
            if ($correctionResult['errorcode'] !== 0) {
                // 异常车
                return $this->abnormalCarNumber($post, $nodeInfo, $entryCarInfo);
            }
            return $this->pass($post);
        }

        // todo 起竿&语音播报
        return $this->mid($post, $nodeInfo, $entryCarInfo, $correctPaths, $carPaths);
    }

    /**
     * 正常放行
     * @param id 流水号
     * @param onduty_id 值班员
     * @param node_id 出场节点
     * @return array
     */
    public function normalPass ($post)
    {
        // 流水号
        $post['id'] = intval($post['id']);
        // 值班员
        $post['onduty_id'] = intval($post['onduty_id']);
        // 节点
        $post['node_id'] = intval($post['node_id']);

        // 查询在场车辆
        if (!$entryCarInfo = $this->entryModel->getCarInfo($post['id'])) {
            return error('该车无入场信息');
        }

        if ($entryCarInfo['current_node_id'] != $post['node_id']) {
            return error('该车不能通过当前通道');
        }

        // 判断车类型
        if ($entryCarInfo['out_car_type'] == CarType::TEMP_CAR) {
            $className = \app\pdo\TempCar::class;
        } else {
            $className = \app\pdo\MemberCar::class;
        }

        $result = (new $className)->normalPass($entryCarInfo);
        if ($result['errorcode'] !== 0) {
            return $result;
        }
        $result = $result['result'];

        // 保存在场信息
        if (!$this->entryModel->saveEntryInfo($entryCarInfo, [
            'pass_type' => $result['passType'],
            'onduty_id' => $post['onduty_id'],
            'broadcast' => $result['broadcast'],
            'signal_type' => $result['signalType'],
            'real_money' => ['money']
        ])) {
            return error('正常放行失败,请重试');
        }

        // 返回信号
        return $this->sendSignal($entryCarInfo['id'], $result['message'], $result['broadcast'], $result['signalType'], []);
    }

    /**
     * 中场
     * @param $post {node_id:节点ID,car_number:车牌号}
     * @param $nodeInfo 节点信息
     * @param $entryCarInfo 入场信息
     * @param $paths 正确路径
     * @param $carPaths {car_type:会员车类型,car_path:会员车路径}
     * @return array
     */
    protected function mid (array $post, array $nodeInfo, array $entryCarInfo, array $paths, array $carPaths)
    {
        // 判断车类型
        if ($carPaths['car_type'] == CarType::TEMP_CAR) {
            $className = \app\pdo\TempCar::class;
        } else if ($carPaths['car_type'] == CarType::MEMBER_CAR) {
            $className = \app\pdo\MemberCar::class;
        } else {
            return error(CarType::getMessage($carPaths['car_type']) . '不能中场');
        }

        // 中场
        $result = (new $className)->mid($nodeInfo);
        // 中场错误
        if ($result['errorcode'] !== 0) {
            return $result;
        }
        $result = $result['result'];

        // 保存在场信息
        if (!$this->entryModel->saveEntryInfo($entryCarInfo, [
            'paths' => json_encode(array_column($paths, 'id')),
            'current_node_id' => $post['node_id'],
            'last_nodes' => json_encode($this->entryModel->connectNode($entryCarInfo['last_nodes'], $post['node_id'])),
            'correction_record' => ['JSON_ARRAY_APPEND(correction_record,"$",' . $post['correction_record_id'] . ')'],
            'pass_type' => $result['passType'],
            'onduty_id' => $post['onduty_id'],
            'broadcast' => $result['broadcast'],
            'signal_type' => $result['signalType']
        ])) {
            return error('中场错误,请重试');
        }

        // 返回信号
        return $this->sendSignal($entryCarInfo['id'], $result['message'], $result['broadcast'], $result['signalType'], []);
    }

    /**
     * 出场&计费
     * @param $post {node_id:节点ID,car_number:车牌号}
     * @param $entryCarInfo 入场信息
     * @param $paths 正确路径
     * @param $carPaths {car_type:会员车类型,car_path:会员车路径}
     * @return array
     */
    protected function out (array $post, array $entryCarInfo, array $paths, array $carPaths)
    {
        $nodes = $this->entryModel->connectNode($entryCarInfo['last_nodes'], $post['node_id']);
        $parameter = [
            '当前时间' => TIMESTAMP,
            '上次入场时间' => strtotime($entryCarInfo['update_time']),
            '上次出场时间' => 0
        ];
        if ($carPaths['car_type'] == CarType::MEMBER_CAR) {
            // 会员车
            $parameter['上次出场时间'] = $this->carModel->getLastOutParkTime($post['car_number']);
        }
        foreach ($nodes as $k => $v) {
            $parameter['节点' . ($k + 1) . 'ID'] = $v['node_id'];
            $parameter['节点' . ($k + 1) . '入场时间'] = strtotime($v['time']);
            if ($k > 0) {
                $parameter['节点' . $k . '_' . ($k + 1) . '停留时间'] = strtotime($v['time']) - strtotime($nodes[$k - 1]['time']);
            }
        }

        // 判断车类型
        if ($carPaths['car_type'] == CarType::TEMP_CAR) {
            $className = \app\pdo\TempCar::class;
        } else if ($carPaths['car_type'] == CarType::MEMBER_CAR) {
            $className = \app\pdo\MemberCar::class;
        } else {
            return error(CarType::getMessage($carPaths['car_type']) . '不能出场');
        }

        // 出场
        $result = (new $className)->out($entryCarInfo, $parameter, $paths, $carPaths['car_path']);
        // 入场错误
        if ($result['errorcode'] !== 0) {
            return $result;
        }
        $result = $result['result'];

        // 保存在场信息
        if (!$this->entryModel->saveEntryInfo($entryCarInfo, [
            'car_id' => $result['carId'],
            'out_car_type' => $result['carType'],
            'paths' => json_encode([$result['pathId']]),
            'money' => $result['money'],
            'current_node_id' => $post['node_id'],
            'last_nodes' => json_encode($nodes),
            'correction_record' => ['JSON_ARRAY_APPEND(correction_record,"$",' . $post['correction_record_id'] . ')'],
            'pass_type' => $result['passType'],
            'onduty_id' => $post['onduty_id'],
            'broadcast' => $result['broadcast'],
            'signal_type' => $result['signalType'],
            'calculation_process' => $result['code']
        ])) {
            return error('出场错误,请重试');
        }

        // 返回信号
        return $this->sendSignal($entryCarInfo['id'], $result['message'], $result['broadcast'], $result['signalType'], []);
    }

    /**
     * 入场
     * @param $post {node_id:节点ID,car_number:车牌号}
     * @param $nodeInfo 节点信息
     * @param $paths 正确路径
     * @param $carPaths {car_type:会员车类型,car_path:会员车路径}
     * @return array
     */
    protected function entry (array $post, array $nodeInfo, array $paths, array $carPaths)
    {
        // 判断车类型
        if ($carPaths['car_type'] == CarType::TEMP_CAR) {
            $className = \app\pdo\TempCar::class;
        } else if ($carPaths['car_type'] == CarType::MEMBER_CAR) {
            $className = \app\pdo\MemberCar::class;
        } else {
            return error(CarType::getMessage($carPaths['car_type']) . '不能入场');
        }

        // 入场
        $result = (new $className)->entry($nodeInfo, $paths, $carPaths['car_path']);
        // 入场错误
        if ($result['errorcode'] !== 0) {
            return $result;
        }
        $result = $result['result'];

        // 添加入场信息
        if (!$id = $this->entryModel->addEntryInfo([
            'car_type' => $carPaths['car_type'],
            'car_id' => $result['carId'],
            'entry_car_type' => $result['carType'],
            'original_car_number' => $post['original_car_number'],
            'car_number' => $post['car_number'],
            'paths' => json_encode(array_column($paths, 'id')),
            'current_node_id' => $post['node_id'],
            'last_nodes' => json_encode([['node_id' => $post['node_id'], 'time' => date('Y-m-d H:i:s', TIMESTAMP)]]),
            'correction_record' => json_encode([$post['correction_record_id']]),
            'pass_type' => $result['passType'],
            'onduty_id' => $post['onduty_id'],
            'broadcast' => $result['broadcast'],
            'signal_type' => $result['signalType']
        ])) {
            return error('入场错误,请重试');
        }

        // 返回信号
        return $this->sendSignal($id, $result['message'], $result['broadcast'], $result['signalType'], []);
    }

    /**
     * 发送信号
     * @param $id 流水号
     * @param $message 提示消息
     * @param $broadcast 语音播报文字
     * @param $status 信号状态
     * @param array $data 显示数据
     * @return array
     */
    protected function sendSignal ($id, $message, $broadcast, $status, array $data = [])
    {
        return success([
            'id' => $id,
            'message' => $message,
            'broadcast' => $broadcast,
            'status' => $status,
            'data' => $data
        ]);
    }

    /**
     * 车牌纠错待处理
     * @param $post
     * @param $errorScene
     * @return array
     */
    protected function correctionLogic (array $post, array $errorScene)
    {
        // 判断第二次错误
        if ($post['error_count']) {
            // 只进行2次纠错
            return error($post);
        }
        // 场景修正
        if (empty($post['correction_scene_count'])) {
            $result = $this->correctionSceneError($post['node_id'], $post['original_car_number'], $post['car_number'], $errorScene, $post['error_count'], $post['correction_record_id']);
            if ($result['errorcode'] === 0) {
                // 场景修正成功
                return success(array_merge($post, $result['result']));
            }
        }
        // 校正车牌
        return $this->correctionCarNumber($post['node_id'], $post['original_car_number'], $post['car_number'], $errorScene);
    }

    /**
     * 异常车通行
     * @param $post
     * @param $nodeInfo 节点信息
     * @param $entryCarInfo 入场信息
     * @return array
     */
    protected function abnormalCarNumber (array $post, array $nodeInfo, $entryCarInfo)
    {
        // 异常车通行
        $result = (new \app\pdo\AbnormalCar())->entry($nodeInfo, [], []);
        if ($result['errorcode'] !== 0) {
            return $result;
        }
        $result = $result['result'];

        // 有入场信息就追加，否则新增入场信息
        if ($entryCarInfo) {
            $id = $entryCarInfo['id'];
            if ($result['signalType'] == SignalType::PASS_SUCCESS) {
                // 起竿放行信号，才追加入场信息，是为了防止车辆驶错通道，造成路径错误问题
                if (!$this->entryModel->saveEntryInfo($entryCarInfo, [
                    'out_car_type' => $result['carType'],
                    'money' => ['money+' . $result['money']],
                    'current_node_id' => $post['node_id'],
                    'last_nodes' => json_encode($this->entryModel->connectNode($entryCarInfo['last_nodes'], $post['node_id'])),
                    'correction_record' => ['JSON_ARRAY_APPEND(correction_record,"$",' . $post['correction_record_id'] . ')'],
                    'pass_type' => $result['passType'],
                    'onduty_id' => $post['onduty_id'],
                    'broadcast' => $result['broadcast'],
                    'signal_type' => $result['signalType']
                ])) {
                    return error('出场错误,请重试');
                }
            }
        } else {
            if (!$id = $this->entryModel->addEntryInfo([
                'car_type' => $result['carType'],
                'entry_car_type' => $result['carType'],
                'original_car_number' => $post['original_car_number'],
                'car_number' => $post['car_number'],
                'money' => $result['money'],
                'current_node_id' => $post['node_id'],
                'last_nodes' => json_encode([['node_id' => $post['node_id'], 'time' => date('Y-m-d H:i:s', TIMESTAMP)]]),
                'correction_record' => json_encode([$post['correction_record_id']]),
                'pass_type' => $result['passType'],
                'onduty_id' => $post['onduty_id'],
                'broadcast' => $result['broadcast'],
                'signal_type' => $result['signalType']
            ])) {
                return error('入场错误,请重试');
            }
        }

        // 返回信号
        return $this->sendSignal($id, $result['message'], $result['broadcast'], $result['signalType'], []);
    }

    /**
     * 校正车牌
     * 1.NO_ENTRY 无入场 NO_START_NODE 不是起点
     * 2.ENTRY    有入场 END_NODE      是终点   PATH_ERROR 路径错误
     * 3.ENTRY    有入场 START_NODE    是起点
     * 4.ENTRY    有入场 MIDDLE_NODE   是中点   PATH_ERROR 路径错误
     * @param $node_id 当前节点
     * @param $original_car_number 原始车牌号
     * @param $car_number 车牌号
     * @param $errorScene 错误场景
     * @param $correctionMessage 消息记录
     * @param $errorCount 校正次数
     * @return array
     */
    protected function correctionCarNumber ($node_id, $original_car_number, $car_number, array $errorScene, array &$correctionMessage = null, $errorCount = 1)
    {
        $original_car_number = $original_car_number ? $original_car_number : $car_number;
        $correctionMessage = is_array($correctionMessage) ? $correctionMessage : [];

        // 获取上个节点
        $lastNodePaths = $this->pathModel->getLastNodePaths($node_id, $errorCount);

        if ($lastNodePaths) {
            // 获取上个节点并且路线正确的入场车
            $entryCar = $this->entryModel->getEntryCurrentNodeCar($lastNodePaths['nodes'], $lastNodePaths['paths']);
            if (empty($entryCar)) {
                return $this->correctionCarNumber($node_id, $original_car_number, $car_number, $errorScene, $correctionMessage, $errorCount + 1);
            }
            // 查找相似车牌
            $newCarNumber = $this->similarCarNumber($original_car_number, $entryCar);
            unset($entryCar);
            $correctionMessage[$errorCount][] = $newCarNumber['message'];
            if ($newCarNumber['errorcode'] !== 0) {
                return $this->correctionCarNumber($node_id, $original_car_number, $car_number, $errorScene, $correctionMessage, $errorCount + 1);
            }
            $newCarNumber = $newCarNumber['result'][0][0];
            if ($errorCount > 1) {
                // 自动修补路线
                $result = $this->entryModel->autoRepairPath($node_id, $newCarNumber);
                $correctionMessage[$errorCount][] = '自动修补路线' . ($result ? '成功' : '失败');
                if ($result) {
                    $correctionMessage[$errorCount][] = $result;
                }
            }
        } else {
            if ($errorCount > 1) {
                // 当前节点不是起点
                // 获取所有入场车
                $entryCar = $this->entryModel->getAllEntryCar();
            } else {
                // 当前节点是起点
                // 获取所有不在场的会员车
                $entryCar = $this->carModel->getAllNoEntryCarNumber();
            }
            // 去掉和原车牌相同的车牌
            if (false !== ($key = array_search($original_car_number, $entryCar))) {
                unset($entryCar[$key]);
            }
            // 查找相似车牌
            $newCarNumber = $this->similarCarNumber($original_car_number, $entryCar);
            unset($entryCar);
            $correctionMessage[$errorCount][] = $newCarNumber['message'];
            if ($newCarNumber['errorcode'] !== 0) {
                $correctionRecordId = $this->saveCorrectionRecord($node_id, $original_car_number, $car_number, $errorScene, $errorCount, $correctionMessage);
                // 修正错误场景
                return $this->correctionSceneError($node_id, $original_car_number, $car_number, $errorScene, $errorCount, $correctionRecordId);
            }
            $newCarNumber = $newCarNumber['result'][0][0];
        }

        // 纠正成功
        $correctionRecordId = $this->saveCorrectionRecord($node_id, $original_car_number, $newCarNumber, $errorScene, $errorCount, $correctionMessage);
        return success([
            'original_car_number' => $original_car_number,
            'car_number' => $newCarNumber,
            'error_count' => $errorCount,
            'error_scene' => $errorScene,
            'correction_record_id' => $correctionRecordId,
        ]);
    }

    /**
     * 修正错误场景
     * @param $node_id 节点ID
     * @param $original_car_number 原始车牌号
     * @param $car_number 车牌号
     * @param $errorScene 错误场景
     * @param $errorCount 错误次数
     * @param $correctionRecordId 车牌纠正记录ID
     * @return array
     */
    protected function correctionSceneError ($node_id, $original_car_number, $car_number, array $errorScene, $errorCount, $correctionRecordId)
    {
        $original_car_number = $original_car_number ? $original_car_number : $car_number;

        if (array_intersect($errorScene, ['ENTRY', 'START_NODE'])) {
            // 进场，有入场信息
            // 1.重复起竿 (起竿后车辆不通过)
            // 2.上次入场记录未移到进出场记录表
            $entryCarInfo = $this->entryModel->find([
                'car_number' => $original_car_number
            ]);
            if ($entryCarInfo) {
                if ($entryCarInfo['current_node_id'] == $node_id) {
                    // 重复进场
                    $record = $this->entryModel->removeEntryCar($entryCarInfo);
                } else {
                    if ($entryCarInfo['out_car_type']) {
                        // 已出场
                        $record = $this->outModel->addOutInfo($entryCarInfo['id']);
                    } else {
                        // 未出场
                        $record = $this->entryModel->removeEntryCar($entryCarInfo);
                    }
                }
                if ($record) {
                    if ($correctionRecordId) {
                        $this->getDb()->update('chemi_correction_record', [
                            'scene_result' => json_mysql_encode($entryCarInfo), 'update_time' => date('Y-m-d H:i:s', TIMESTAMP)
                        ], ['id' => $correctionRecordId]);
                    }
                    return success([
                        'original_car_number' => $original_car_number,
                        'car_number' => $original_car_number, // 换成原车牌
                        'error_count' => $errorCount,
                        'error_scene' => $errorScene,
                        'correction_record_id' => $correctionRecordId,
                        'correction_scene_count' => 1
                    ]);
                }
            }
        }

        return error([
            'original_car_number' => $original_car_number,
            'car_number' => $car_number,
            'error_count' => $errorCount,
            'error_scene' => $errorScene,
            'correction_record_id' => $correctionRecordId,
        ]);
    }

    /**
     * 添加纠正记录
     * @param $node_id
     * @param $original_car_number
     * @param $car_number
     * @param $errorScene
     * @param $errorCount
     * @param $correctionMessage
     * @return bool
     */
    protected function saveCorrectionRecord ($node_id, $original_car_number, $car_number, array $errorScene, $errorCount, array $correctionMessage)
    {
        $car_number = $original_car_number == $car_number ? null : $car_number;
        return $this->getDb()->insert('chemi_correction_record', [
            'node_id' => $node_id, 'original_car_number' => $original_car_number, 'car_number' => $car_number, 'error_scene' => json_encode($errorScene), 'error_count' => $errorCount, 'message' => json_encode($correctionMessage), 'update_time' => date('Y-m-d H:i:s', TIMESTAMP), 'create_time' => date('Y-m-d H:i:s', TIMESTAMP)
        ], null, false, true);
    }

    /**
     * 比较两个车牌相似度
     * @param $first 第一个车牌
     * @param $second 第二个车牌
     * @return array
     */
    protected function similarCarNumber ($first, array $second)
    {
        if (empty($first) || empty($second)) {
            return error('未找到匹配车牌');
        }

        $weight = [
            '贵' => ['青', '鲁', '湘', '鄂', '皖', '津', '蒙', '川', '黑'],
            '青' => ['贵'],
            '鲁' => ['贵'],
            '湘' => ['贵'],
            '鄂' => ['贵'],
            '皖' => ['贵'],
            '津' => ['贵'],
            '蒙' => ['贵'],
            '川' => ['贵'],
            '黑' => ['贵'],
            '0' => ['D', '6'],
            '1' => ['J', 'L', '4', 'A', 'T'],
            '2' => ['Z', '3'],
            '3' => ['2', '8', '5'],
            '4' => ['C', '1'],
            '5' => ['B', 'S', '3'],
            '6' => ['B', 'C', '8', '0'],
            '7' => ['T'],
            '8' => ['B', '3', '6', 'J'],
            'A' => ['B', '1'],
            'B' => ['5', '8', '6', 'A'],
            'C' => ['G', '4', '6'],
            'D' => ['0'],
            'G' => ['C'],
            'J' => ['1', '8'],
            'L' => ['1'],
            'S' => ['5'],
            'T' => ['7', '1'],
            'U' => ['V'],
            'V' => ['U'],
            'Z' => ['2']
        ];
        $firstWord = mb_str_split($first);
        $firstWordLength = count($firstWord);
        $secondLength = count($second);
        $weightVector = round(1 / $firstWordLength, $firstWordLength);
        $firstNumber = substr($first, 3);
        $firstNumberLength = strlen($firstNumber);
        $minMatchPercent = 2;
        $similarResult = [];
        // 编辑距离
        foreach ($second as $v) {
            $secondNumber = substr($v, 3);
            if (strlen($secondNumber) == $firstNumberLength) {
                $similarPercent = levenshtein($firstNumber, $secondNumber);
                if ($similarPercent <= $minMatchPercent) {
                    $similarResult[$v] = $similarPercent;
                }
            }
        }
        unset($second);
        if (empty($similarResult)) {
            return error('共查询' . $secondLength . '个车牌，未发现编辑距离大于' . $minMatchPercent . '的相似车牌');
        }
        asort($similarResult);
        $similarResult = array_slice($similarResult, 0, 20);
        // 相似度
        foreach ($similarResult as $k => $v) {
            similar_text($first, $k, $similarPercent);
            $similarResult[$k] = [
                $k, $v, round($similarPercent, $firstWordLength)
            ];
        }
        $similarResult = array_values($similarResult);
        // 权重
        foreach ($similarResult as $k => $v) {
            $splitCarNumber = mb_str_split($v[0]);
            $weightValue = [];
            foreach ($splitCarNumber as $kk => $vv) {
                $char = $firstWord[$kk];
                if (!isset($char)) {
                    continue;
                }
                $weightScore = 0;
                if ($vv == $char) {
                    $weightScore = 1;
                } else if (isset($weight[$vv])) {
                    if (false !== ($weightKey = array_search($char, $weight[$vv]))) {
                        $weightScore = (99 - $weightKey) / 100;
                    }
                }
                $weightValue[] = round($weightScore * $weightVector, $firstWordLength);
            }
            $similarResult[$k][] = array_sum($weightValue);
            $similarResult[$k][] = implode(' + ', $weightValue);
        }
        array_multisort(array_column($similarResult, 3), SORT_DESC, SORT_NUMERIC, $similarResult);
        // 权重大于0.855
        if ($similarResult[0][3] < 0.855) {
            return error($similarResult, '共查询' . $secondLength . '个车牌，找到相似车牌“' . $similarResult[0][0] . '”，相似度' . $similarResult[0][2] . '，最大权重' . $similarResult[0][3] . '不达标');
        }
        return success($similarResult, '共查询' . $secondLength . '个车牌，找到相似车牌“' . $similarResult[0][0] . '”，相似度' . $similarResult[0][2] . '，权重值' . $similarResult[0][3]);
    }

    /**
     * 路径验证
     * @param $node_id 当前节点
     * @param $paths   多条路径
     * @param $lastNodes 路径节点记录 [{"node_id":node_id,"time":time}]
     * @return array 返回正确路径
     */
    protected function verifyPath ($node_id, array $paths, array $lastNodes)
    {
        $lastNodes = $this->entryModel->connectNode($lastNodes, $node_id);
        $lastNodes = array_column($lastNodes, 'node_id');
        $path = [];
        foreach ($paths as $k => $v) {
            if ($v['nodes'] === $lastNodes) {
                $path[] = $v;
            }
        }
        return $path;
    }

    /**
     * 查询车辆类型
     * @param $car_number 车牌号
     * @return array
     */
    protected function getCarType ($car_number)
    {
        // 会员车
        $result = $this->carModel->getCarType($car_number);

        // todo 商户车、共享车 ...

        if (empty($result)) {
            // 默认为临时车
            $result = [
                'car_type' => CarType::TEMP_CAR, 'car_path' => [], 'paths' => $this->pathModel->getTempCarPath()
            ];
        }

        return $result;
    }

    /**
     * 获取指定路径
     * @param $paths 多条路径
     * @param $node_id 当前节点
     * @param $node 指定节点类型
     * @return array
     */
    protected function diffPath (array $paths, $node_id, $node = null)
    {
        $path = [];
        foreach ($paths as $k => $v) {
            if ($node == 'start_node') {
                if ($v['start_node'] == $node_id) {
                    $path[] = $v;
                }
            } else if ($node == 'end_node') {
                if ($v['end_node'] == $node_id) {
                    $path[] = $v;
                }
            } else {
                if (count($v['nodes']) > 2) {
                    $nodes = $v['nodes'];
                    unset($nodes[count($nodes) - 1] ,$nodes[0]);
                    if (false !== ($nodeKey = array_search($node_id, $nodes))) {
                        $v['nodes'] = array_slice($v['nodes'], 0, $nodeKey + 1);
                        $path[] = $v;
                    }
                    unset($nodes);
                }
            }
        }
        return $path;
    }

}
