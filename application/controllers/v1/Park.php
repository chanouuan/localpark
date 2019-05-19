<?php

namespace app\controllers;

use ActionPDO;
use app\models\ParkModel;

/**
 * 线下停车场接口
 * @Date 2019-04-07
 */
class Park extends ActionPDO {

    public function __ratelimit ()
    {
        return [
            'pass' => [
                'url' => getgpc('node_id'),
                'interval' => 1000
            ],
            'normalPass' => [
                'url' => getgpc('node_id'),
                'interval' => 1000
            ],
            'abnormalPass' => [
                'url' => getgpc('node_id'),
                'interval' => 1000
            ],
            'revokePass' => [
                'url' => getgpc('node_id'),
                'interval' => 1000
            ]
        ];
    }

    /**
     * 车辆进出场
     * @login
     * @param *car_number 车牌号
     * @param *node_id 通道ID
     * @param error_count 手动纠错车牌,此值为1
     * @return array
     * {
     * "errNo":0, // 错误码 0成功 -1失败
     * "message":"", //错误消息
     * "result":{
     *     "id":1, //流水号
     *     "message":"一路顺风", //提示消息
     *     "broadcast":"一路顺风", //语音播报文字
     *     "status":1, //信号状态 1起竿放行 2弹窗+异常放行+禁止通行 3弹窗+正常放行+撤销出场
     *     "data":[], //显示数据
     * }}
     */
    public function pass ()
    {
        return (new ParkModel())->pass([
            'node_id' => $_POST['node_id'], 'car_number' => $_POST['car_number'], 'error_count' => $_POST['error_count'], 'onduty_id' => $this->_G['user']['uid']
        ]);
    }

    /**
     * 正常放行
     * @login
     * @param *id 流水号
     * @param *node_id 通道ID
     * @return array
     * {
     * "errNo":0, // 错误码 0成功 -1失败
     * "message":"", //错误消息
     * "result":{
     *     "id":1, //流水号
     *     "message":"一路顺风", //提示消息
     *     "broadcast":"一路顺风", //语音播报文字
     *     "status":1, //信号状态 1起竿放行 2弹窗+异常放行+禁止通行 3弹窗+正常放行+撤销出场
     *     "data":[], //显示数据
     * }}
     */
    public function normalPass ()
    {
        return (new ParkModel())->normalPass([
            'id' => $_POST['id'], 'node_id' => $_POST['node_id'], 'onduty_id' => $this->_G['user']['uid']
        ]);
    }

    /**
     * 异常放行
     * @login
     * @param *id 流水号
     * @param *node_id 通道ID
     * @return array
     * {
     * "errNo":0, // 错误码 0成功 -1失败
     * "message":"", //错误消息
     * "result":{
     *     "id":1, //流水号
     *     "message":"一路顺风", //提示消息
     *     "broadcast":"一路顺风", //语音播报文字
     *     "status":1, //信号状态 1起竿放行
     *     "data":[], //显示数据
     * }}
     */
    public function abnormalPass ()
    {
        return (new ParkModel())->abnormalPass([
            'id' => $_POST['id'], 'node_id' => $_POST['node_id'], 'onduty_id' => $this->_G['user']['uid']
        ]);
    }

    /**
     * 撤销放行
     * @login
     * @param *id 流水号
     * @param *node_id 通道ID
     * @return array
     * {
     * "errNo":0, // 错误码 0成功 -1失败
     * "message":"", //错误消息
     * "result":{
     *     "id":1, //流水号
     *     "message":"一路顺风", //提示消息
     *     "broadcast":"一路顺风", //语音播报文字
     *     "status":1, //信号状态 0无动作
     *     "data":[], //显示数据
     * }}
     */
    public function revokePass ()
    {
        return (new ParkModel())->revokePass([
            'id' => $_POST['id'], 'node_id' => $_POST['node_id'], 'onduty_id' => $this->_G['user']['uid']
        ]);
    }

}
