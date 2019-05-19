<?php
/**
 * 节点位置类型
 */

namespace app\common;

class DotType
{
    const START_DOT     = 1;
    const MID_DOT       = 2;
    const END_DOT       = 3;
    const END_START_DOT = 4;

    static $message = [
        1 => '起点',
        2 => '中点',
        3 => '终点',
        4 => '终点与起点'
    ];

    /**
     * 是否终点
     * @param $code
     * @return bool
     */
    public static function isEndDot ($code)
    {
        return in_array($code, [
            self::END_DOT, self::END_START_DOT
        ]);
    }

    /**
     * 获取终点
     * @param $start_node
     * @return int
     */
    public static function getEndDot ($start_node = false)
    {
        return $start_node ? self::END_START_DOT : self::END_DOT;
    }

    public static function getMessage ($code)
    {
        return isset(self::$message[$code]) ? self::$message[$code] : '';
    }

}
