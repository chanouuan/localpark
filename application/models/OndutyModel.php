<?php

namespace app\models;

use app\common\PassType;
use app\common\PayType;
use Crud;

class OndutyModel extends Crud {

    /**
     * 获取值班员收银账目
     * @param $onduty_id 值班员ID
     * @return array
     */
    public function getOndutyCash ($onduty_id)
    {
        if (!$ondutyInfo = $this->getDb()->table('chemi_onduty_charge')->field('money,detail,create_time')->where(['onduty_id' => $onduty_id, 'end_time' => null])->order('id desc')->limit(1)->find()) {
            return error('该值班员未开始值班');
        }
        $ondutyInfo['detail'] = json_decode($ondutyInfo['detail'], true);
        $detail = [];
        foreach ($ondutyInfo['detail'] as $k => $v) {
            foreach ($v as $kk => $vv) {
                $detail[PassType::getMessage($k)][PayType::getMessage($kk)] = round_dollar($vv);
            }
        }
        $ondutyInfo['detail'] = $detail;
        $ondutyInfo['money']  = round_dollar($ondutyInfo['money']);
        return success($ondutyInfo);
    }

    /**
     * 值班员交接班
     * @param $original_onduty_id 原始值班员ID
     * @param $onduty_id 当前值班员ID
     * @return bool
     */
    public function change ($original_onduty_id, $onduty_id)
    {
        $this->getDb()->update('chemi_onduty_charge', [
            'update_time' => date('Y-m-d H:i:s', TIMESTAMP),
            'end_time'    => date('Y-m-d H:i:s', TIMESTAMP)
        ], [
            'onduty_id' => ['in', [$original_onduty_id, $onduty_id]],
            'end_time'  => null
        ]);
        return $this->getDb()->insert('chemi_onduty_charge', [
            'onduty_id'   => $onduty_id,
            'update_time' => date('Y-m-d H:i:s', TIMESTAMP),
            'create_time' => date('Y-m-d H:i:s', TIMESTAMP)
        ]);
    }

    /**
     * 值班员缴费
     * @param $onduty_id 值班员ID
     * @return bool
     */
    public function charge ($onduty_id)
    {
        $ondutyInfo = $this->getDb()->table('chemi_onduty_charge')->field('id,create_time')->where(['onduty_id' => $onduty_id, 'end_time' => null])->order('id desc')->limit(1)->find();
        if (empty($ondutyInfo)) {
            // 新增
            $ondutyInfo = [
                'onduty_id'   => $onduty_id,
                'update_time' => date('Y-m-d H:i:s', TIMESTAMP),
                'create_time' => date('Y-m-d H:i:s', TIMESTAMP)
            ];
            if (!$ondutyInfo['id'] = $this->getDb()->insert('chemi_onduty_charge', $ondutyInfo, null, false, true)) {
                return false;
            }
        }

        // 统计收费
        $entryList = $this->getDb()->table('chemi_entry')->field('pass_type,pay_type,sum(real_money) as real_money')->where(['onduty_id' => $onduty_id, 'update_time' => ['>=', $ondutyInfo['create_time']]])->group('pass_type,pay_type')->select();
        $outList   = $this->getDb()->table('chemi_out')->field('pass_type,pay_type,sum(real_money) as real_money')->where(['onduty_id' => $onduty_id, 'update_time' => ['>=', $ondutyInfo['create_time']]])->group('pass_type,pay_type')->select();

        $detail = [];
        $totalMoney = 0;
        foreach ($entryList as $k => $v) {
            $totalMoney += $v['real_money'];
            $detail[$v['pass_type']][$v['pay_type']] += $v['real_money'];
        }
        foreach ($outList as $k => $v) {
            $totalMoney += $v['real_money'];
            $detail[$v['pass_type']][$v['pay_type']] += $v['real_money'];
        }

        return $this->getDb()->update('chemi_onduty_charge', [
            'money'       => $totalMoney,
            'detail'      => json_encode($detail),
            'update_time' => date('Y-m-d H:i:s', TIMESTAMP)
        ], ['id' => $ondutyInfo['id']]);
    }

}
