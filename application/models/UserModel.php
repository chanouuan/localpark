<?php

namespace app\models;

use Crud;

class UserModel extends Crud {

    protected $table = 'pro_user';

    /**
     * 登录状态设置
     * @return array
     */
    public function setloginstatus ($uid, $scode, $opt = [], $expire = 0)
    {
        if (!$uid) {
            return error('no session!');
        }
        $update = [
            'userid' => $uid,
            'scode' => $scode,
            'clienttype' => CLIENT_TYPE,
            'clientinfo' => null,
            'loginip' => get_ip(),
            'online' => 1,
            'updated_at' => date('Y-m-d H:i:s', TIMESTAMP)
        ];
        !empty($opt) && $update = array_merge($update, $opt);
        if (!$this->getDb()->norepeat('__tablepre__session', $update)) {
            return error('session error!');
        }
        $token = rawurlencode(authcode("$uid\t$scode\t{$update['clienttype']}", 'ENCODE'));
        set_cookie('token', $token, $expire);
        return success([
            'token' => $token
        ]);
    }

    /**
     * 登出
     * @return bool
     */
    public function logout ($uid, $clienttype = null)
    {
        if (!$this->getDb()->update('__tablepre__session', [
            'scode' => 0,
            'online' => 0,
            'updated_at' => date('Y-m-d H:i:s', TIMESTAMP)
        ], [
            'userid' => $uid,
            'clienttype' => get_real_val($clienttype, CLIENT_TYPE)
        ])) {
            return false;
        }
        set_cookie('token', null);
        return true;
    }

    /**
     * hash密码
     * @param $pwd
     * @return string
     */
    public function hashPassword ($pwd)
    {
        return password_hash($pwd, PASSWORD_BCRYPT);
    }

    /**
     * 密码hash验证
     * @param $pwd 密码明文
     * @param $hash hash密码
     * @return bool
     */
    public function passwordVerify ($pwd, $hash)
    {
        return password_verify($pwd, $hash);
    }

}
