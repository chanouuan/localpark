<?php

namespace app\common;

class CarType
{
    const TEMP_CAR = 1;
    const MEMBER_CAR = 2;

    static $message = [
        1  => '临时车',
        2 => '会员车'
    ];

    public static function getMessage ($code) {
        return isset(self::$message[$code]) ? self::$message[$code] : '';
    }

}