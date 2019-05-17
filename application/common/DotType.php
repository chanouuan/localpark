<?php
/**
 * 节点位置类型
 */

namespace app\common;

class DotType
{
    const START_DOT = 1;
    const MID_DOT   = 2;
    const END_DOT   = 3;

    static $message = [
        1 => '起点',
        2 => '中点',
        3 => '终点'
    ];

    public static function getMessage ($code)
    {
        return isset(self::$message[$code]) ? self::$message[$code] : '';
    }

}
