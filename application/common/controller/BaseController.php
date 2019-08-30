<?php

namespace app\common\controller;

use app\common\business\User;
use think\Controller;

class BaseController extends Controller
{

    /**
     * 输出错误的信息
     * @param string $message
     * @param int $status
     */
    protected function _echoErrorMessage($message = '', $status = 0)
    {
        $return = array(
            'code' => $status,
            'msg' => $message,
        );
        $this->_displayJson($return);
    }

    /**
     * 请求时输出成功的信息,并退出
     * @param string $code
     * @param string $message
     * @param array $data
     */
    protected function _echoSuccessMessage($code,$message, $data = array())
    {
        $return = array(
            'code' => $code,
            'msg' => $message,
            'data' => $data
        );
        $this->_displayJson($return);
    }

    /**
     * 输出json格式的数据
     *
     * @param mixed $data
     */
    public function _displayJson($data)
    {
        header('Content-type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:GET, POST, PUT');
        header('Access-Control-Allow-Headers:x-requested-with,content-type,sign,keytime');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * 判断是否登录，未登录不允许往下操作
     * @return int|mixed
     */
    public function _isLogin(){
        $token = $this->_getToken();
        if (!$token) {
            exception('请先登录',10001);
        }
        $memberId = User::decodeToken($token);
        if (!$memberId) {
            exception('请先登录',10002);
        }
        $member = User::getUserInfoByUserId($memberId);
        if (!$member) {
            exception('请先登录',10003);
        }
        return $member['user_id'];
    }

    /**
     * 判断是否登录 不强制登录
     * @return int|mixed
     */
    public function _checkLogin(){
        $token = $this->_getToken();
        if (!$token) {
            return 0;
        }
        $memberId = User::decodeToken($token);
        if (!$memberId) {
            return 0;
        }
        $member = User::getUserInfoByUserId($memberId);
        if (!$member) {
            return 0;
        }
        return $member['user_id'];
    }

    /**
     * 获取token
     * @return string
     */
    public function _getToken()
    {
        $token = $this->getCookie("token");
        if (!$token) {
            $token = input('token', '');
            if (!$token) {
                $token = empty($_REQUEST["token"]) ? "" : $_REQUEST["token"];
            }
        }
        if (strlen($token) <= 10) {
            $token = "";
        }

        return $token;
    }

    /**
     * 获取cookie信息
     * @param $key
     * @param string $default
     * @return string
     */
    public function getCookie($key, $default = "")
    {
        $post = input();
        if ($key == 'token' && isset($post['token']) && $post['token']) {
            return $post['token'];
        }
        if (isset($_REQUEST[$key]) && $key == 'token') {
            if ($this->is_base64($_REQUEST[$key])) {
                $_REQUEST[$key] = base64_decode($_REQUEST[$key]);
            }
        }
        if (isset($_REQUEST[$key])) {
            return $_REQUEST[$key];
        }
        if (isset($_COOKIE[$key]) && strlen($_COOKIE[$key]) > 0) ;
        if (isset($_COOKIE[$key]) && $key == 'token') {
            if ($this->is_base64($_COOKIE[$key])) {
                $_COOKIE[$key] = base64_decode($_COOKIE[$key]);
            }
            return $_COOKIE[$key];
        }
        return $default;
    }

    /**
     * 判断是否为base64
     * @param $str
     * @return bool
     */
    public function is_base64($str)
    {
        if ($str == base64_encode(base64_decode($str))) {
            return true;
        } else {
            return false;
        }
    }
}
