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

        if (empty($entryCarInfo)) {
            // 如果不在场内，就判断是否是起点
            $startPaths = $this->diffPathStart($carPaths['paths'], $post['node_id']);
            if (empty($startPaths)) {
                // 如果不是起点
                // 1.无入场信息
                // 2.车牌识别错
                // todo 车牌纠正
            } else {
                // 如果是起点
                // 判断是否允许入场
                // 1.临时车是否允许入场
                // 2.会员车失效后是否允许入场 (月卡过期、余额不足)
                // 3.多卡多车，附属车位满后是否允许入场
            }
        }

        print_r($carPaths);

        return success([]);
    }

    /**
     * 查询车辆类型
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
     * 获取路径为指定起点的路径
     */
    protected function diffPathStart ($paths, $node_id)
    {
        $path = [];
        foreach ($paths as $k => $v) {
            if ($v['start_node'] == $node_id) {
                $paths[] = $v;
            }
        }
        return $path;
    }

}
