<?php
namespace app\api\controller;

use app\common\controller\BaseController;
use think\Exception;

class Index extends BaseController
{
    public function index()
    {
        try{
            $user_id = $this->_isLogin();
        }catch (Exception $e){
            $this->_echoErrorMessage($e->getMessage(),$e->getCode());
        }
    }
}