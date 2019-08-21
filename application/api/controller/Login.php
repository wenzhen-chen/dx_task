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
            $result = User::mobileLogin($param['mobile'],$param['password']);
            $this->_echoSuccessMessage($result['code'],$result['msg'],['sso' => $result['sso']]);
        } catch (\Exception $e) {
            $this->_echoErrorMessage($e->getMessage(), $e->getCode());
        }
    }
}