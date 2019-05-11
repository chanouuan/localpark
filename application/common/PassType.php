<?php
/**
 * 通行方式
 */
namespace app\common;

class PassType
{
    const WAIT_PASS     = 0;
    const NORMAL_PASS   = 1;
    const ABNORMAL_PASS = 2;
    const MANUAL_PASS   = 3;

    static $message = [
        0 => '等待放行',
        1 => '正常通行',
        2 => '异常放行',
        3 => '手动起竿放行'
    ];

    public static function getMessage ($code)
    {
        return isset(self::$message[$code]) ? self::$message[$code] : '';
    }

}
