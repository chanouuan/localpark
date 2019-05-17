<?php
/**
 * 通行方式
 */

namespace app\common;

class PassType
{
    const NORMAL_PASS   = 1;
    const ABNORMAL_PASS = 2;
    const WAIT_PASS     = 4;
    const REVOKE_PASS   = 5;

    static $message = [
        1 => '正常通行',
        2 => '异常放行',
        4 => '等待放行',
        5 => '撤销通行'
    ];

    public static function getMessage ($code)
    {
        return isset(self::$message[$code]) ? self::$message[$code] : '';
    }

}
