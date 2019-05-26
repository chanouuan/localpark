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
                'url'      => getgpc('node_id'),
                'interval' => 1000
            ],
            'normalPass' => [
                'url'      => getgpc('node_id'),
                'interval' => 1000
            ],
            'abnormalPass' => [
                'url'      => getgpc('node_id'),
                'interval' => 1000
            ],
            'revokePass' => [
                'url'      => getgpc('node_id'),
                'interval' => 1000
            ],
            'ondutyLogin' => [
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
            'node_id'     => $_POST['node_id'],
            'car_number'  => $_POST['car_number'],
            'error_count' => $_POST['error_count'],
            'onduty_id'   => $this->_G['user']['uid']
        ]);
    }

    /**
     * 正常放行
     * @login
     * @param *id 流水号
     * @param *node_id 通道ID
     * @param *pay_type 支付方式(1现金2微信3支付宝)
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
            'id'        => $_POST['id'],
            'node_id'   => $_POST['node_id'],
            'pay_type'  => $_POST['pay_type'],
            'onduty_id' => $this->_G['user']['uid']
        ]);
    }

    /**
     * 异常放行
     * @login
     * @param *id 流水号
     * @param *node_id 通道ID
     * @param *pay_type 支付方式(1现金2微信3支付宝)
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
            'id'        => $_POST['id'],
            'node_id'   => $_POST['node_id'],
            'pay_type'  => $_POST['pay_type'],
            'onduty_id' => $this->_G['user']['uid']
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
            'id'        => $_POST['id'],
            'node_id'   => $_POST['node_id'],
            'onduty_id' => $this->_G['user']['uid']
        ]);
    }

    /**
     * 值班员登录
     * @param token 登录Token,用于值班员交接班
     * @param *username 用户名
     * @param *password 密码
     * @return array
     * {
     * "errNo":0, // 错误码 0成功 -1失败
     * "message":"", // 返回信息
     * "result":{
     *     "uid":1, //用户ID
     *     "telephone":"", //手机号
     *     "nickname":"", //昵称
     *     "gender":1, //性别 0未知 1男 2女
     *     "token":"", //登录凭证
     * }}
     */
    public function ondutyLogin ()
    {
        if ($_POST['token']) {
            if ($userInfo = $this->loginCheck($_POST['token'])) {
                $_POST['original_onduty_id'] = $userInfo['uid'];
            }
        }
        return (new ParkModel())->ondutyLogin($_POST);
    }

    /**
     * 获取值班员收银账目
     * @login
     * @return array
     * {
     * "errNo":0, // 错误码 0成功 -1失败
     * "message":"", // 返回信息
     * "result":{
     *     "money":0, //已收款 (元)
     *     "detail":{}, //费用明细
     *     "create_time":"", //值班开始时间
     * }}
     */
    public function getOndutyCash ()
    {
        return (new ParkModel())->getOndutyCash($this->_G['user']['uid']);
    }

}
