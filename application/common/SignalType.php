<?php
/**
 * 信号发送类型
 */
namespace app\common;

class SignalType
{
    const PASS_SUCCESS            = 1;
    const CONFIRM_ABNORMAL_CANCEL = 2;
    const CONFIRM_NORMAL_CANCEL   = 3;

    static $message = [
        1 => '起竿放行',
        2 => '弹窗+异常放行+禁止入场',
        3 => '弹窗+正常放行+撤销出场'
    ];

    public static function getMessage ($code)
    {
        return isset(self::$message[$code]) ? self::$message[$code] : '';
    }

}
