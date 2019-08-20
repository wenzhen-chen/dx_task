<?php
namespace app\api\controller;


use app\common\controller\BaseController;

class Login extends BaseController
{
    public function mobileLogin() {
        try {
        } catch (\Exception $e) {
            $this->_echoErrorMessage($e->getMessage(), $e->getCode());
        }
    }
}