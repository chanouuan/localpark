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

        // 查询在场车辆
        $entryCarInfo = $this->entryModel->getCarInfo($post['car_number']);

        // 获取当前节点是起点的路径
        $startPaths = $this->diffPath($carPaths['paths'], $post['node_id'], 'start_node');

        // 判断是否是在场车
        if (empty($entryCarInfo)) {
            if (empty($startPaths)) {
                // 如果不是起点
                // 1.无入场信息
                // 2.车牌识别错
                // todo 车牌纠正
                $post = array_merge($post, $this->correctionCarNumber($post['node_id'], $post['original_car_number'], $post['car_number'], ['NO_ENTRY'], $post['error_count'] ++));
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

        // 获取当前节点是终点的路径
        $endPaths = $this->diffPath($carPaths['paths'], $post['node_id'], 'end_node');

        // 判断是否终点
        if ($endPaths) {
            // 终点路径验证
            $correctPaths = $this->verifyPath($post['node_id'], $endPaths, $entryCarInfo['last_nodes']);
            if (empty($correctPaths)) {
                // 1.车牌识别错
                // todo 车牌纠正
                $post = array_merge($post, $this->correctionCarNumber($post['node_id'], $post['original_car_number'], $post['car_number'], ['ENTRY', 'END_NODE', 'PATH_ERROR'], $post['error_count'] ++));
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
            // 1.车牌识别错
            // 2.上次出场异常 (系统错误、跟车...)
            // todo 车牌纠正
            $post = array_merge($post, $this->correctionCarNumber($post['node_id'], $post['original_car_number'], $post['car_number'], ['ENTRY', 'START_NODE'], $post['error_count'] ++));
            return $this->pass($post);
        }

        // 获取当前节点是节点的路径
        $nodePaths = $this->diffPath($carPaths['paths'], $post['node_id']);
        if (empty($nodePaths)) {
            return error('此通道禁止通行');
        }

        // 节点路径验证
        $correctPaths = $this->verifyPath($post['node_id'], $nodePaths, $entryCarInfo['last_nodes']);
        if (empty($correctPaths)) {
            // 1.车牌识别错
            // todo 车牌纠正
            $post = array_merge($post, $this->correctionCarNumber($post['node_id'], $post['original_car_number'], $post['car_number'], ['ENTRY', 'PATH_ERROR'], $post['error_count'] ++));
            return $this->pass($post);
        }

        // todo 出场确认&计费
        // 是节点起竿正常通行

        return success([]);
    }

    /**
     * 校正车牌
     * @param $node_id 当前节点
     * @param $original_car_number 原始车牌号
     * @param $car_number 车牌号
     * @param $scene 错误场景
     * @param $errorCount 校正次数
     * @return array
     */
    protected function correctionCarNumber ($node_id, $original_car_number, $car_number, array $scene, $errorCount)
    {
        $original_car_number = $original_car_number ? $original_car_number : $car_number;
        // 获取上个节点

        // 纠正失败
        // todo 异常车牌处理
    }

    /**
     * 路径验证
     * @param $node_id 当前节点
     * @param $paths   多条路径
     * @param $lastNodes 路径节点记录 {{node_id:time}}
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
