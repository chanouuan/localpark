<?php

namespace app\common;

class AbnormalCarPassWay
{
    const AUTO_PASS   = 1;
    const CHARGE      = 2;
    const MANUAL_PASS = 3;

    static $message = [
        1 => '自动放行',
        2 => '异常收费',
        3 => '手动放行'
    ];

    public static function getMessage ($code) {
        return isset(self::$message[$code]) ? self::$message[$code] : '';
    }

}
