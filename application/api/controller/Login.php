<?php
namespace app\api\controller;

use app\common\business\User;
use app\common\controller\BaseController;

class Login extends BaseController
{
    /**
     * 手机号+密码登录
     */
    public function mobileLogin() {
        try {
            $param = input();
            //验证数据
            $validate = Validate('User');
            $post = $validate->scene('login')->check($param);
            if (!$post) {
                $this->_echoSuccessMessage('success', ['code' => -10001, 'msg' => $validate->getError()]);
            }
            $data = User::mobileLogin($param['mobile'],$param['password']);
            $this->_echoSuccessMessage('success', $data);
        } catch (\Exception $e) {
            $this->_echoErrorMessage($e->getMessage(), $e->getCode());
        }
    }
}