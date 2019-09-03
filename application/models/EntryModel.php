<?php

namespace app\models;

use app\common\PassType;
use Crud;
use app\common\CarType;
use app\common\DotType;

class EntryModel extends Crud {

    protected $table = 'chemi_entry';

    /**
     * 获取在场车辆信息
     * @param $id id/车牌号
     * @return array
     */
    public function getCarInfo ($id)
    {
        if (is_integer($id)) {
            $condition['id'] = $id;
        } else {
            $condition['car_number'] = $id;
        }
        $info = $this->find($condition, 'id,car_type,car_id,car_number,current_car_type,paths,current_node_id,last_nodes,version_count,pass_type,dot,update_time');
        if ($info) {
            // 当前路径
            $info['paths'] = $info['paths'] ? json_decode($info['paths'], true) : [];
            // 节点记录 [{"node_id":node_id,"car_type":car_type,"time":time}]
            $info['last_nodes'] = $info['last_nodes'] ? json_decode($info['last_nodes'], true) : [];
        }
        return $info;
    }

    /**
     * 添加入场信息
     * @param $data
     * @return bool
     */
    public function addEntryInfo (array $data)
    {
        $data['update_time'] = date('Y-m-d H:i:s', TIMESTAMP);
        $data['create_time'] = date('Y-m-d H:i:s', TIMESTAMP);
        if (!$id = $this->getDb()->insert($this->table, $data, null, false, true)) {
            return false;
        }
        // 值班员缴费
        if ($data['onduty_id'] && $data['real_money']) {
            (new OndutyModel())->charge($data['onduty_id']);
        }
        // 更新车位数
        if ($data['car_type'] == CarType::TEMP_CAR) {
            // 临时车车位数-1
            $this->getDb()->update('chemi_node', [
                'temp_car_left' => ['temp_car_left-1']
            ], [
                'id' => $data['current_node_id'], 'temp_car_count' => ['>', 0], 'temp_car_left' => ['>', 0]
            ]);
        }
        if ($data['car_type'] == CarType::MEMBER_CAR && CarType::isMemberLeftCar($data['current_car_type'])) {
            // 会员车车位数-1
            $this->getDb()->update('chemi_car_path', [
                'place_left' => ['place_left-1']
            ], 'place_left > 0 and JSON_CONTAINS(car_number,\'"' . $data['car_number'] . '"\')');
        }
        return $id;
    }

    /**
     * 保存入场信息
     * @param $entryInfo
     * @param $data
     * @return bool
     */
    public function saveEntryInfo (array $entryInfo, array $data)
    {
        $data['version_count'] = ['version_count+1'];
        $data['update_time'] = $data['update_time'] ? $data['update_time'] : date('Y-m-d H:i:s', TIMESTAMP);
        if (!$this->getDb()->update($this->table, $data, [
            'id' => $entryInfo['id'], 'version_count' => $entryInfo['version_count']
        ])) {
            return false;
        }
        // 值班员缴费
        if ($data['onduty_id'] && $data['real_money']) {
            (new OndutyModel())->charge($data['onduty_id']);
        }
        // 撤销通行
        if ($data['pass_type'] == PassType::REVOKE_PASS) {
            if ($entryInfo['current_node_id'] && $data['current_node_id'] && $entryInfo['current_node_id'] != $data['current_node_id']) {
                // 更新车位数
                if ($entryInfo['current_car_type'] == CarType::TEMP_CAR) {
                    // 不是终点
                    if (!DotType::isEndDot($entryInfo['dot'])) {
                        // 临时车车位数+1
                        $this->getDb()->update('chemi_node', [
                            'temp_car_left' => ['temp_car_left+1']
                        ], [
                            'id' => $entryInfo['current_node_id'], 'temp_car_count' => ['>temp_car_left']
                        ]);
                    }
                }
                if ($data['current_car_type'] == CarType::TEMP_CAR) {
                    // 临时车车位数-1
                    $this->getDb()->update('chemi_node', [
                        'temp_car_left' => ['temp_car_left-1']
                    ], [
                        'id' => $data['current_node_id'], 'temp_car_count' => ['>', 0], 'temp_car_left' => ['>', 0]
                    ]);
                }
                if ($entryInfo['car_type'] == CarType::MEMBER_CAR && CarType::isMemberLeftCar($entryInfo['current_car_type'])) {
                    // 会员车车位数-1
                    if (DotType::isEndDot($entryInfo['dot'])) {
                        $this->getDb()->update('chemi_car_path', [
                            'place_left' => ['place_left-1']
                        ], 'place_left > 0 and JSON_CONTAINS(car_number,\'"' . $entryInfo['car_number'] . '"\')');
                    }
                }
            }
            return true;
        }
        // 不是重复提交
        if ($entryInfo['current_node_id'] && $data['current_node_id'] && $entryInfo['current_node_id'] != $data['current_node_id']) {
            // 更新车位数
            if ($entryInfo['current_car_type'] == CarType::TEMP_CAR) {
                // 临时车车位数+1
                $this->getDb()->update('chemi_node', [
                    'temp_car_left' => ['temp_car_left+1']
                ], [
                    'id' => $entryInfo['current_node_id'], 'temp_car_count' => ['>temp_car_left']
                ]);
            }
            if ($data['current_car_type'] == CarType::TEMP_CAR) {
                // 不是终点
                if (!DotType::isEndDot($data['dot'])) {
                    // 当前节点临时车车位数-1
                    $this->getDb()->update('chemi_node', [
                        'temp_car_left' => ['temp_car_left-1']
                    ], [
                        'id' => $data['current_node_id'], 'temp_car_count' => ['>', 0], 'temp_car_left' => ['>', 0]
                    ]);
                }
            }
            if ($entryInfo['car_type'] == CarType::MEMBER_CAR && CarType::isMemberLeftCar($data['current_car_type'])) {
                // 会员车车位数+1
                if (DotType::isEndDot($data['dot'])) {
                    $this->getDb()->update('chemi_car_path', [
                        'place_left' => ['place_left+1']
                    ], 'place_count > place_left and JSON_CONTAINS(car_number,\'"' . $entryInfo['car_number'] . '"\')');
                }
            }
        }
        return true;
    }

    /**
     * 删除入场车
     * @return bool
     */
    public function removeEntryCar (array $entryInfo)
    {
        if (DotType::isEndDot($entryInfo['dot'])) {
            // 已经出场的车不能删除
            return false;
        }
        // 删除入场车
        if (!$this->getDb()->delete($this->table, ['id' => $entryInfo['id']])) {
            return false;
        }
        // 更新车位数
        if ($entryInfo['current_car_type'] == CarType::TEMP_CAR) {
            // 临时车车位数+1
            $this->getDb()->update('chemi_node', [
                'temp_car_left' => ['temp_car_left+1']
            ], [
                'id' => $entryInfo['current_node_id'], 'temp_car_count' => ['>temp_car_left']
            ]);
        }
        return true;
    }

    /**
     * 连接节点
     * @param $last 上节点
     * @param $node_id 当前节点
     * @param $car_type 当前车辆类型
     * @param $money 金额
     * @return string
     */
    public function connectNode (array $last, $node_id, $car_type = 0, $money = 0)
    {
        $nodeSize = count($last);
        if ($nodeSize > 0) {
            if ($last[$nodeSize - 1]['node_id'] == $node_id) {
                $last[$nodeSize - 1]['car_type'] = $car_type;
                $last[$nodeSize - 1]['money']    = $money;
                $last[$nodeSize - 1]['time']     = date('Y-m-d H:i:s', TIMESTAMP);
                return $last;
            }
        }
        $last[] = [
            'node_id'  => $node_id,
            'car_type' => $car_type,
            'money'    => $money,
            'time'     => date('Y-m-d H:i:s', TIMESTAMP)
        ];
        return $last;
    }

    /**
     * 自动修补路线
     * @param $node_id 当前节点ID
     * @param $car_number 纠正后的车牌号
     * @return bool
     */
    public function autoRepairPath ($node_id, $car_number)
    {
        if (!$entryCarInfo = $this->getCarInfo($car_number)) {
            return false;
        }
        if ($entryCarInfo['current_node_id'] == $node_id) {
            return false;
        }
        // 判断哪条路径可以到达当前节点node_id
        if (!$paths = $this->getDb()->table('chemi_path')->field('id,nodes')->where('id in (' . implode(',', $entryCarInfo['paths']) . ') and JSON_CONTAINS(nodes,"' . $node_id . '")')->select()) {
            return false;
        }
        // 如果有多条路径可以到达，取路径最短的一条
        $pathId = 0;
        $passNodes = [];
        foreach ($paths as $k => $v) {
            $v['nodes'] = json_decode($v['nodes'], true);
            if (false === ($currentNodekey = array_search($entryCarInfo['current_node_id'], $v['nodes']))) {
                continue;
            }
            if (!$nodeKey = array_search($node_id, $v['nodes'])) {
                // 当前节点是起点
                continue;
            }
            if ($nodeKey - $currentNodekey <= 1) {
                // 节点无需修补
                continue;
            }
            // 获取到达当前节点，需要经过的点
            $pass = array_slice($v['nodes'], $currentNodekey + 1, $nodeKey - $currentNodekey - 1);
            if (empty($pass)) {
                continue;
            }
            if ($passNodes) {
                if (count($pass) >= count($passNodes)) {
                    continue;
                }
            }
            $passNodes = $pass;
            $pathId = $v['id'];
        }
        if (empty($passNodes)) {
            return false;
        }
        // 按平均法修补节点
        $diffTime = intval((TIMESTAMP - strtotime($entryCarInfo['update_time'])) / (count($passNodes) + 1));
        $updateTime = null;
        foreach ($passNodes as $k => $v) {
            $updateTime = date('Y-m-d H:i:s', $entryCarInfo['update_time'] + ($diffTime * ($k + 1)));
            $entryCarInfo['last_nodes'][] = [
                'node_id'  => $v,
                'car_type' => $entryCarInfo['current_car_type'],
                'time'     => $updateTime,
                'auto'     => 1
            ];
        }
        $param = [
            'paths' => json_encode([$pathId]),
            'current_node_id' => end($passNodes),
            'last_nodes'      => json_encode($entryCarInfo['last_nodes']),
            'update_time'     => $updateTime
        ];
        if (!$this->saveEntryInfo($entryCarInfo, $param)) {
            return false;
        }
        return $param;
    }

    /**
     * 获取通过指定节点的入场车
     * @param array $nodes_id 节点列表
     * @param array $path_id 路径列表
     * @return array
     */
    public function getEntryCurrentNodeCar (array $nodes_id, array $path_id)
    {
        $condition = [
            'current_node_id in (' . implode(',', $nodes_id) . ')'
        ];
        $param = [];
        foreach ($path_id as $k => $v) {
            $param[] = 'JSON_CONTAINS(paths,"' . $v . '")';
        }
        $param = '(' . implode(' OR ', $param) . ')';
        $condition[] = $param;
        $list = $this->select($condition, 'car_number');
        if ($list) {
            $list = array_column($list, 'car_number');
        }
        return $list;
    }

    /**
     * 获取所有入场车
     * @return array
     */
    public function getAllEntryCar ()
    {
        $list = $this->select(null, 'car_number');
        if ($list) {
            $list = array_column($list, 'car_number');
        }
        return $list;
    }

}
