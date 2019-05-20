<?php
/**
 * 播报类型
 */

namespace app\common;

class BroadcastType
{
    const MEMBER_CAR_DAY_ENTRY     = 1;
    const MEMBER_CAR_INVALID_ENTRY = 2;
    const CAR_ENTRY                = 3;
    const CAR_PAY_OUT              = 4;
    const CAR_OUT                  = 5;
    const STORE_CARD_CAR_OUT       = 6;
    const MEMBER_CAR_INVALID_OUT   = 7;
    const PLACE_LIMIT_ENTRY        = 8;
    const REVOKE_PASS              = 9;

    /**
     * 获取播报内容
     * @param $code
     * @param $data
     * @return array {"voice":txt,"display":txt}
     */
    public static function getContent ($code, array $data)
    {
        if (false === F('broadcasts')) {
            if (!$list = \app\library\DB::getInstance()->table('chemi_broadcast_template')->field('id,voice,display')->select()) {
                return [];
            }
            $list = array_column($list, null, 'id');
            $list = array_key_clean($list, ['id']);
            F('broadcasts', $list);
        }
        $list = F('broadcasts');
        if (!isset($list[$code])) {
            return [];
        }
        $list = $list[$code];
        foreach ($list as $k => $v) {
            $list[$k] = template_replace($v, $data);
        }
        return $list;
    }

}
