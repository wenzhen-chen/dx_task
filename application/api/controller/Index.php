<?php
namespace app\api\controller;

use app\common\business\Task;
use app\common\controller\BaseController;
use app\common\redis\db0\TaskCache;
use think\cache\driver\Redis;
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
    public function test(){
    }
}