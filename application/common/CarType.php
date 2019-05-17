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
    const CHILD_CAR      = 10;
    const ORDINARY_CAR   = 12;
    const INVALID_CAR    = 14;
    const PAY_CAR        = 16;
    const ABNORMAL_CAR   = 18;
    const MEMBER_CAR     = 100;


    static $message = [
        1   => '临时车',
        2   => '月卡车',
        3   => '贵宾车',
        4   => '固定车',
        5   => '储值卡车',
        10  => '附属车',
        12  => '普通车',
        14  => '过期车',
        16  => '缴费车',
        18  => '异常车',
        100 => '会员车'
    ];

    /**
     * 是否会员车
     * @param $code
     * @return bool
     */
    public static function isMemberCar ($code)
    {
        return in_array($code, [
            self::MONTH_CARD_CAR, self::VIP_CAR,   self::FIXED_CAR,
            self::STORE_CARD_CAR, self::CHILD_CAR, self::ORDINARY_CAR,
            self::INVALID_CAR,    self::PAY_CAR,   self::MEMBER_CAR
        ]);
    }

    public static function getMessage ($code)
    {
        return isset(self::$message[$code]) ? self::$message[$code] : '';
    }

}
