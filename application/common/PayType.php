<?php
/**
 * 收费端支付方式
 */

namespace app\common;

class PayType
{
    const CASH   = 1;
    const WXPAY  = 2;
    const ALIPAY = 3;

    static $message = [
        1 => '现金',
        2 => '微信',
        3 => '支付宝'
    ];

    public static function getCode ($code)
    {
        return isset(self::$message[$code]) ? $code : 0;
    }

    public static function getMessage ($code)
    {
        return isset(self::$message[$code]) ? self::$message[$code] : '';
    }

}
