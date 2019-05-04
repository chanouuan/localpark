<?php

namespace app\models;

use Crud;
use app\common\CarType;

class ParkModel extends Crud {

    protected $nodeModel;
    protected $carModel;
    protected $pathModel;
    protected $entryModel;

    public function __construct()
    {
        // 加载 model
        $this->nodeModel  = new NodeModel();
        $this->carModel   = new CarModel();
        $this->pathModel  = new PathModel();
        $this->entryModel = new EntryModel();
    }

    /**
     * 车辆进出场
     * @param node_id
     * @param original_car_number
     * @param car_number
     * @param error_count
     * @return array
     */
    public function pass ($post)
    {
        // 节点
        $post['node_id'] = intval($post['node_id']);

        // 车牌号验证
        if (!check_car_license($post['car_number'])) {
            return error('车牌号错误');
        }

        // 验证节点
        if (!$nodeInfo = $this->nodeModel->get($post['node_id'])) {
            return error('节点未找到');
        }

        // 查询车牌类型 (临时车、会员车 ...)
        $carPaths = $this->getCarType($post['car_number']);

        if (empty($carPaths['paths'])) {
            return error(CarType::getMessage($carPaths['car_type']) . '，不能进场。');
        }

        // 获取当前节点是起点的路径
        $startPaths = $this->diffPath($carPaths['paths'], $post['node_id'], 'start_node');
        // 获取当前节点是中点的路径
        $nodePaths = $this->diffPath($carPaths['paths'], $post['node_id']);
        // 获取当前节点是终点的路径
        $endPaths = $this->diffPath($carPaths['paths'], $post['node_id'], 'end_node');

        // 已纠正过，第二次验证
        if ($post['error_count']) {
            if (empty($startPaths) && empty($nodePaths) && empty($endPaths)) {
                return error('此通道禁止通行');
            }
        }

        // 查询在场车辆
        $entryCarInfo = $this->entryModel->getCarInfo($post['car_number']);

        // 判断是否是在场车
        if (empty($entryCarInfo)) {
            if (empty($startPaths)) {
                // 第二次错，进入异常车流程
                if ($post['error_count']) {
                    return $this->abnormalCarNumber($post);
                }
                // 如果不是起点
                // 1.无入场信息
                // 2.车牌识别错
                // todo 车牌纠正
                $post = array_merge($post, $this->correctionCarNumber($post['node_id'], $post['original_car_number'], $post['car_number'], ['NO_ENTRY', 'NO_START_NODE']));
                return $this->pass($post);
            } else {
                // 如果是起点
                // 判断是否允许入场
                // 1.临时车是否允许入场
                // 2.会员车失效后是否允许入场 (月卡过期、余额不足)
                // 3.多卡多车，附属车位满后是否允许入场
                // 4.有可能以错车牌入场
                // todo 入场确认
            }
        }

        // 每个节点状态
        // 1.起点      不在场
        // 2.节点      在场
        // 3.终点      在场
        // 4.终点&起点 在场

        // 判断是否终点
        if ($endPaths) {
            // 终点路径验证
            $correctPaths = $this->verifyPath($post['node_id'], $endPaths, $entryCarInfo['last_nodes']);
            if (empty($correctPaths)) {
                // 第二次错，进入异常车流程
                if ($post['error_count']) {
                    return $this->abnormalCarNumber($post);
                }
                // 1.车牌识别错
                // todo 车牌纠正
                $post = array_merge($post, $this->correctionCarNumber($post['node_id'], $post['original_car_number'], $post['car_number'], ['ENTRY', 'END_NODE', 'PATH_ERROR']));
                return $this->pass($post);
            }
            // todo 出场确认&计费
            // 判断是否起点
            if ($startPaths) {
                // 既是终点又是起点
                return $this->pass($post);
            }
        }

        // 判断是否起点
        if ($startPaths) {
            // 第二次错，进入异常车流程
            if ($post['error_count']) {
                return $this->abnormalCarNumber($post);
            }
            // 1.车牌识别错
            // 2.上次出场异常 (系统错误、跟车...)
            // todo 车牌纠正
            $post = array_merge($post, $this->correctionCarNumber($post['node_id'], $post['original_car_number'], $post['car_number'], ['ENTRY', 'START_NODE']));
            return $this->pass($post);
        }

        // 节点路径验证
        $correctPaths = $this->verifyPath($post['node_id'], $nodePaths, $entryCarInfo['last_nodes']);
        if (empty($correctPaths)) {
            // 第二次错，进入异常车流程
            if ($post['error_count']) {
                return $this->abnormalCarNumber($post);
            }
            // 1.车牌识别错
            // todo 车牌纠正
            $post = array_merge($post, $this->correctionCarNumber($post['node_id'], $post['original_car_number'], $post['car_number'], ['ENTRY', 'MIDDLE_NODE', 'PATH_ERROR']));
            return $this->pass($post);
        }

        // todo 出场确认&计费
        // 是节点起竿正常通行

        return success([]);
    }

    /**
     * 处理异常车
     * @param node_id
     * @param original_car_number
     * @param car_number
     * @param error_count
     * @return array
     */
    protected function abnormalCarNumber ($post)
    {

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
     * @param $scene 错误场景
     * @param $errorCount 校正次数
     * @return array
     */
    protected function correctionCarNumber ($node_id, $original_car_number, $car_number, array $scene, $errorCount = 1)
    {
        $original_car_number = $original_car_number ? $original_car_number : $car_number;

        // 获取上个节点
        $lastNodePaths = $this->pathModel->getLastNodePaths($node_id, $errorCount);
        if ($lastNodePaths) {
            // 获取上个节点并且路线正确的入场车
            $entryCar = $this->entryModel->getEntryCurrentNodeCar($lastNodePaths['nodes'], $lastNodePaths['paths']);
            if (empty($entryCar)) {
                return $this->correctionCarNumber($node_id, $original_car_number, $car_number, $scene, $errorCount + 1);
            }
            // 查找相似车牌
            $newCarNumber = $this->similarCarNumber($original_car_number, $entryCar);
            if ($newCarNumber['errorcode'] !== 0) {
                return $this->correctionCarNumber($node_id, $original_car_number, $car_number, $scene, $errorCount + 1);
            }
            $newCarNumber = $newCarNumber['result'][0][0];
            if ($errorCount > 1) {
                // 自动修补路线
                $this->entryModel->autoRepairPath($node_id, $newCarNumber);
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
            if (empty($entryCar)) {
                // 纠正失败
                return [
                    'original_car_number' => $original_car_number,
                    'car_number' => $car_number,
                    'errorCount' => $errorCount
                ];
            }
            // 查找相似车牌
            $newCarNumber = $this->similarCarNumber($original_car_number, $entryCar);
            if ($newCarNumber['errorcode'] !== 0) {
                return [
                    'original_car_number' => $original_car_number,
                    'car_number' => $car_number,
                    'errorCount' => $errorCount
                ];
            }
            $newCarNumber = $newCarNumber['result'][0][0];
        }

        // 纠正成功
        return [
            'original_car_number' => $original_car_number,
            'car_number' => empty($newCarNumber) ? $car_number : $newCarNumber,
            'errorCount' => $errorCount
        ];
    }

    /**
     * 比较两个车牌相似度
     * @param $first 第一个车牌
     * @param $second 第二个车牌
     * @return array
     */
    protected function similarCarNumber ($first, array $second)
    {
        $weight = [
            '贵' => ['鲁'],
            '鲁' => ['贵'],
            '0' => ['D', '6'],
            '1' => ['J', 'L'],
            '2' => ['Z'],
            '3' => ['8'],
            '5' => ['B'],
            '6' => ['C', '8', '0'],
            '8' => ['B', '3', '6'],
            'B' => ['5', '8'],
            'C' => ['G'],
            'D' => ['0'],
            'G' => ['C'],
            'J' => ['1'],
            'L' => ['1'],
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
                if ($similarPercent >= $minMatchPercent) {
                    $similarResult[$v] = $similarPercent;
                }
            }
        }
        unset($second);
        if (empty($similarResult)) {
            return error('共查询' . $secondLength . '个车牌，未发现编辑距离大于' . $minMatchPercent . '的相似车牌');
        }
        asort($similarResult);
        $similarResult = array_slice($similarResult, 0, 10);
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
                } else if (isset($weight[$char])) {
                    if (false !== ($weightKey = array_search($vv, $weight[$char]))) {
                        $weightScore = (99 - $weightKey) / 100;
                    }
                }
                $weightValue[] = round($weightScore * $weightVector, $firstWordLength);
            }
            $similarResult[$k][] = array_sum($weightValue);
            $similarResult[$k][] = implode(' + ', $weightValue);
        }
        array_multisort(array_column($similarResult, 3), SORT_DESC, SORT_NUMERIC, $similarResult);
        // 权重大于0.85
        if ($similarResult[0][2] < 0.85) {
            return error($similarResult, '共查询' . $secondLength . '个车牌，最大权重' . $similarResult[0][2] . '不达标');
        }
        return success($similarResult, '共查询' . $secondLength . '个车牌，找到相似车牌“' . $similarResult[0][0] . '”，相似度' . $similarResult[0][2] . '，权重值' . $similarResult[0][3]);
    }

    /**
     * 路径验证
     * @param $node_id 当前节点
     * @param $paths   多条路径
     * @param $lastNodes 路径节点记录 {{node_id:{time:time}}}
     * @return array 返回正确路径
     */
    protected function verifyPath ($node_id, $paths, $lastNodes)
    {
        $lastNodes = array_keys($lastNodes);
        $lastNodes[] = $node_id;
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
                'car_type' => CarType::TEMP_CAR, 'paths' => $this->pathModel->getTempCarPath()
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
    protected function diffPath ($paths, $node_id, $node = null)
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
