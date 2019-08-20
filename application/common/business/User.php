<?php
namespace app\common\business;

use app\common\mysql\User as userMysql;

class User extends AbstractModel
{
    /**
     * 解密用户登录的sso信息
     * @param $sso
     * @return mixed
     */
    public static function decodeSso($sso)
    {
        $key = Config('auth_key');

        $result = authcode(rawurldecode($sso), "DECODE", $key);
        return $result;
    }

    /**
     * 根据用户id查询用户信息
     * @param $userId
     * @return array|null|\PDOStatement|string|\think\Model
     */
    public static function getUserInfoByUserId($userId)
    {
        $model = new userMysql();
        return $model->getInfo('user_id=' . $userId);
    }
}