<?php
namespace app\common\business;

use app\common\mysql\User as userMysql;

class User extends AbstractModel
{
    /**
     * 解密用户登录的sso信息
     * @param $token
     * @return mixed
     */
    public static function decodeToken($token)
    {
        $key = Config('auth_key');

        $result = authcode(rawurldecode($token), "DECODE", $key);
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

    /**
     * 计算用户登录的sso信息
     */
    public static function calToken($userId)
    {
        $key = Config('auth_key');

        $token = rawurlencode(authcode($userId, "ENCODE", $key));

        return $token;
    }

    /**
     * 手机号+密码登录
     * @param $mobile
     * @param $password
     * @return array
     */
    public static function mobileLogin($mobile, $password)
    {
        //1、根据手机号码查询用户密码
        $userInfo = self::getUserInfoByMobile($mobile, 'user_id,password');
        //2、验证密码是否正确
        $code = -1;
        $msg = '用户名或密码不正确';
        $token = '';
        if ($userInfo['password'] == md5($password)) {
            $code = 0;
            $msg = '登录成功';
            $token = self::calToken($userInfo['user_id']);
        }
        return [
            'code' => $code,
            'msg' => $msg,
            'token' => $token
        ];
    }

    /**
     * 根据手机号获取用户信息
     * @param $mobile
     * @param $field
     * @return array|null|\PDOStatement|string|\think\Model
     * @author wenzhen-chen
     * @time 2019-8-21
     */
    public static function getUserInfoByMobile($mobile,$field = '')
    {
        $model = new userMysql();
        $model->field = $field;
        return $model->getInfo('mobile=' . $mobile);
    }

}