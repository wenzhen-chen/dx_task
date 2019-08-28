<?php

namespace app\common\business;

use app\common\mysql\TaskLog;
use app\common\mysql\TaskModule;
use app\common\mysql\Task as taskMysql;
use app\common\redis\db0\TaskCache;

class Task extends AbstractModel
{
    /**
     * 任务分类列表
     * @param $where
     * @param $page
     * @param $pageSize
     * @return array
     * @author wenzhen-chen
     * @time 2019-8-11
     */
    public static function getModuleList($where, $page, $pageSize)
    {
        $model = new TaskModule();
        $offset = ($page - 1) * $pageSize;
        $list = $model->getList($where, $offset, $pageSize);
        $total = $model->countData($where);
        return [
            'list' => $list,
            'total' => $total
        ];
    }

    /**
     *  前端任务模块列表
     * @param $where
     * @param $page
     * @param $pageSize
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getFontModuleList($where, $page, $pageSize)
    {
        $model = new TaskModule();
        $offset = ($page - 1) * $pageSize;
        $list = $model->getList($where, $offset, $pageSize);
        $data = [];
        foreach ($list as $item) {
            if (isset($item['lastTime'])) {
                $item['lastTime'] = date('H:i', $item['lastTime']);
            }
            if (isset($item['logo'])) {
                $item['logo'] = config('api_url') . $item['logo'];
            }
            $msgNum = self::countTaskByModuleId($item['id']);
            $item['msgNum'] = $msgNum;
            $data[] = $item;
        }
        $has_more = count($data) == $pageSize ? 1 : 0;
        return [
            'list' => $data,
            'has_more' => $has_more
        ];
    }

    /**
     * 根据任务模块id统计
     * @param $moduleId
     * @return float|string
     */
    public static function countTaskByModuleId($moduleId)
    {
        $model = new taskMysql();
        $where = 'moduleId=' . $moduleId;
        return $model->countData($where);
    }

    /**
     * 获取任务模块详情
     * @param $where
     * @param $field
     * @return array|null|\PDOStatement|string|\think\Model
     */
    public static function getModuleInfo($where, $field = '')
    {
        $model = new TaskModule();
        $model->field = $field;
        return $model->getInfo($where);
    }

    /**
     * 提交社群任务
     * @param $post
     * @return array
     */
    public static function addGroupTask($post)
    {
        //1、检测社群任务是否存在
        $where = 'id=' . $post['moduleId'];
        $moduleInfo = self::getModuleInfo($where);
        if (empty($moduleInfo)) {
            $code = -1;
            $msg = '模块不存在';
        } else {
            //2、添加任务
            $post['createTime'] = $post['updateTime'] = time();
            $model = new taskMysql();
            $newId = $model->addInfo($post);
            if ($newId) {
                $code = 0;
                $msg = '提交成功';
                //3、添加任务redis缓存
                $redis = new TaskCache();
                $redis->addTask($post['price'], $newId, $post['number']);
            } else {
                $code = -2;
                $msg = '提交失败';
            }
        }
        return [
            'code' => $code,
            'msg' => $msg
        ];
    }

    /**
     * 派发任务
     * @param $userId
     * @param $moduleId
     * @return array
     */
    public static function getMaxGroupTask($userId, $moduleId)
    {
        $code = -1;
        $msg = '无空闲任务';
        $taskInfo = [];
        $taskList = [];
        $taskId = 0;
        $redis = new TaskCache();
        //1、查询用户进行中的任务
        $currentTask = $redis->getUserCurrentTask($userId);
        if (!empty($currentTask) && $currentTask['endTime'] > time()) {
            $taskId = $currentTask['taskId'];
            $countdown = $currentTask['endTime'] - time();
        } else {
            //2、查询redis,获取任务id及余量
            $cacheInfo = $redis->getMaxTask($userId);
            if ($cacheInfo['taskId']) {
                $countdown = 300;
                //3、添加用户任务
                $res = self::addUserTask($userId, $taskId);
                if ($res) {
                    $taskId = $cacheInfo['taskId'];
                }
            }
        }
        if ($taskId) {
            //3、查询任务信息
            $taskInfo = self::userGetTaskByTaskId($userId, $taskId);
            $taskInfo['countdown'] = $countdown;//任务剩余时间
            $code = 0;
            $msg = '获取成功';
            //4、查询最新任务列表
            $where = 'moduleId=' . $moduleId . ' and status=0';
            $ext_condition = [
                'order' => 'createTime deac',
                'field' => 'logo,title,createTime,price'
            ];
            $taskList = self::getFontTaskList($where, 1, 10,$ext_condition)['list'];
        }
        return [
            'code' => $code,
            'msg' => $msg,
            'data' => [
                'taskInfo' => $taskInfo,
                'taskList' => $taskList
            ]
        ];
    }

    /**
     * 获取用户当前任务信息
     * @param $userId
     * @param $taskId
     * @return mixed
     */
    public static function userGetTaskByTaskId($userId, $taskId)
    {
        //1、获取任务详情
        $model = new taskMysql();
        $model->field = 'user.userHead,user.nikeName,module.id groupId,module.name groupName,task.title,task.id,task.desc,task.logo,task.images,task.createTime,task.price,task.number';
        $where = 'task.id=' . $taskId . ' and task.userId=' . $userId;
        $data = $model->getInfo($where);
        $taskInfo['id'] = $data['id'];
        $taskInfo['userId'] = $userId;
        $taskInfo['userHead'] = $data['userHead'];
        $taskInfo['nikeName'] = $data['nikeName'];
        $taskInfo['groupId'] = $data['groupId'];
        $taskInfo['groupName'] = $data['groupName'];
        $taskInfo['title'] = $data['title'];
        $taskInfo['desc'] = $data['desc'];
        $taskInfo['logo'] = $data['logo'];
        $taskInfo['images'] = $data['images'];
        $taskInfo['createTime'] = date('y-m-d', $data['createTime']);
        $taskInfo['price'] = $data['price'];
        $taskInfo['remain'] = 1;//@
        $taskInfo['num'] = $data['number'];
        $taskInfo['status'] = 'pendding';//@
        $taskInfo['isHot'] = 1;//@
        return $taskInfo;
    }

    /**
     * 添加任务记录
     * @param $userId
     * @param $taskId
     * @return int|string
     */
    public static function addUserTask($userId, $taskId)
    {
        //1、查询任务截止时间
        $redis = new TaskCache();
        $taskInfo = $redis->getUserCurrentTask($userId);
        $time = time();
        $endTime = $taskInfo['endTime'];
        $logModel = new TaskLog();
        $logData = [
            'userId' => $userId,
            'taskId' => $taskId,
            'createTime' => $time,
            'updateTime' => $time,
            'endTime' => $endTime,
        ];
        return $logModel->addInfo($logData);
    }

    /**
     * 前台获取任务列表
     * @param $where
     * @param $page
     * @param $pageSize
     * @param $ext_condition
     * @return array
     */
    public static function getFontTaskList($where, $page, $pageSize, $ext_condition = [])
    {
        $order = isset($ext_condition['order']) ? $ext_condition['order'] : '';
        $field = isset($ext_condition['field']) ? $ext_condition['field'] : '';
        $model = new taskMysql();
        $model->order = $order;
        $model->field = $field;
        $offset = ($page - 1) * $pageSize;
        $list = $model->getList($where, $offset, $pageSize);
        $data = [];
        foreach ($list as $item) {
            if (isset($item['createTime'])) {
                $item['createTime'] = date('H:i', $item['createTime']);
            }
            if (isset($item['logo'])) {
                $item['logo'] = config('api_url') . $item['logo'];
            }
            $data[] = $item;
        }
        $has_more = count($data) == $pageSize ? 1 : 0;
        return [
            'list' => $data,
            'has_more' => $has_more
        ];
    }
}