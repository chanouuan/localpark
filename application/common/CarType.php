<?php
/**
 * 车辆类型
 */
namespace app\common;

class CarType
{
    const TEMP_CAR       = 1;
    const MONTH_CARD_CAR = 2;
    const VIP_CAR        = 3;
    const FIXED_CAR      = 4;
    const STORE_CARD_CAR = 5;
    const ORDINARY_CAR   = 10;
    const INVALID_CAR    = 13;
    const PAY_CAR        = 15;
    const MEMBER_CAR     = 100;
    const CHILD_CAR      = 101;

    static $message = [
        1   => '临时车',
        2   => '月卡车',
        3   => '贵宾车',
        4   => '固定车',
        5   => '储值卡车',
        10  => '普通车',
        13  => '过期车',
        15  => '缴费车',
        100 => '会员车',
        101 => '附属车'
    ];

    public static function getMessage ($code)
    {
        return isset(self::$message[$code]) ? self::$message[$code] : '';
    }

}
