<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/8/22
 * Time: 21:01
 */

namespace app\api\controller;

use app\common\controller\BaseController;
use app\common\business\Task as taskBn;

class Task extends BaseController
{
    /**
     * 社群列表
     */
    public function groupTaskModule()
    {
        try {
            $page = input('page', 1);
            $pageSize = input('pageSize', 10);
            $list = taskBn::getFontModuleList('type=1', $page, $pageSize);
            $this->_echoSuccessMessage(
                0, '获取成功', $list
            );
        } catch (\Exception $e) {
            $this->_echoErrorMessage($e->getMessage(), $e->getCode());
        }
    }

    /**
     * 发布社群任务
     */
    public function groupPublish()
    {
        try {
            $param = input();
            //验证数据
            $validate = Validate('Task');
            $post = $validate->scene('groupTask')->check($param);
            if (!$post) {
                $this->_echoSuccessMessage('success', ['code' => -10001, 'msg' => $validate->getError()]);
            }
            unset($param['token']);
            $param['module_id'] = $param['groupId'];//前端传groupId，后端存module_id
            unset($param['groupId']);
            $userId = $this->_isLogin();
            $result = taskBn::addGroupTask($userId, $param);
            $this->_echoSuccessMessage($result['code'], $result['msg']);
        } catch (\Exception $e) {
            $this->_echoErrorMessage($e->getMessage(), $e->getCode());
        }
    }

    /**
     * 社群任务派发
     */
    public function getGroupTask()
    {
        try {
            $userId = $this->_isLogin();

            $moduleId = input('groupId', 0);
            $taskInfo = taskBn::getMaxGroupTask($userId, $moduleId);
            $this->_echoSuccessMessage($taskInfo['code'], $taskInfo['msg'], $taskInfo['data']);
        } catch (\Exception $e) {
            $this->_echoErrorMessage($e->getMessage(), $e->getCode());
        }
    }

    /**
     * 提交任务
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author wenzhen-chen
     * @time 2019-9-2
     */
    public function submitTask()
    {
        $userId = $this->_isLogin();
        $param = input();
        //验证数据
        $validate = Validate('Task');
        $post = $validate->scene('submitTask')->check($param);
        if (!$post) {
            $this->_echoSuccessMessage('success', ['code' => -10001, 'msg' => $validate->getError()]);
        }
        $taskId = $param['taskId'];
        $images = $param['images'];
        $res = taskBn::submitTask($userId, $taskId, $images);
        $this->_echoSuccessMessage($res['code'], $res['msg']);
    }

}