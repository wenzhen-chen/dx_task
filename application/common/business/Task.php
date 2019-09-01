<?php

namespace app\common\business;

use app\common\mysql\TaskLog;
use app\common\mysql\TaskModule;
use app\common\mysql\Task as taskMysql;
use app\common\redis\db0\TaskCache;
use think\Db;

class Task extends AbstractModel
{
    const STATUS_WAIT = 0;//待提交
    const STATUS_SUBMIT = 1;//已提交
    const STATUS_DONE = 2;//已完成
    const STATUS_DIS = 3;//纠纷

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
            if (isset($item['last_time'])) {
                $item['lastTime'] = date('H:i', $item['last_time']);
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
        $where = 'module_id=' . $moduleId;
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
     * @param $userId
     * @param $post
     * @return array
     */
    public static function addGroupTask($userId, $post)
    {
        //1、检测用户金额
        $djBalance = User::getUserDouJin($userId);
        $djNeed = $post['number'] * $post['price'];
        if ($djBalance > $djNeed) {
            //2、检测社群任务是否存在
            $where = 'id=' . $post['module_id'];
            $moduleInfo = self::getModuleInfo($where);
            if (empty($moduleInfo)) {
                $code = -1;
                $msg = '模块不存在';
            } else {
                try {
                    Db::startTrans();
                    //3、添加任务
                    $post['create_time'] = $post['update_time'] = time();
                    $post['user_id'] = $userId;
                    $model = new taskMysql();
                    $newId = $model->addInfo($post);

                    //4、扣除抖金
                    $djData['order_id'] = $newId;
                    $djData['type'] = 2;
                    $djData['remark'] = '发布任务';
                    $djData['balance'] = $djBalance - $djNeed;
                    Finance::updateUserDouJin(Finance::OPT_SUB, $userId, $djNeed, $djData);

                    //5、添加任务redis缓存
                    $redis = new TaskCache();
                    $redis->addTask($post['price'], $newId, $post['number']);

                    Db::commit();
                    $code = 0;
                    $msg = '提交成功';
                } catch (\Exception $e) {
                    $code = -2;
                    $msg = '提交失败';
                    Db::rollback();
                }
            }
        } else {
            $code = -3;
            $msg = '抖金余额不足，请充值';
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
                $res = self::addUserTask($userId, $cacheInfo['taskId']);
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
            $where = 'module_id=' . $moduleId . ' and status=0';
            $ext_condition = [
                'order' => 'create_time desc',
                'field' => 'logo,title,create_time,price'
            ];
            $taskList = self::getFontTaskList($where, 1, 10, $ext_condition)['list'];
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
        $model->field = 'user.avatar,user.nickname,module.id group_id,module.name group_name,task.title,task.id,task.desc,task.logo,task.images,task.create_time,task.price,task.number';
        $where = 'task.id=' . $taskId . ' and task.user_id=' . $userId;
        $data = $model->getInfo($where);
        $taskInfo['id'] = $data['id'];
        $taskInfo['userId'] = $userId;
        $taskInfo['userHead'] = $data['avatar'];
        $taskInfo['nikeName'] = $data['nickname'];
        $taskInfo['groupId'] = $data['group_id'];
        $taskInfo['groupName'] = $data['group_name'];
        $taskInfo['title'] = $data['title'];
        $taskInfo['desc'] = $data['desc'];
        $taskInfo['logo'] = $data['logo'];
        $taskInfo['images'] = $data['images'];
        $taskInfo['createTime'] = date('y-m-d', $data['create_time']);
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
            'user_id' => $userId,
            'task_id' => $taskId,
            'create_time' => $time,
            'update_time' => $time,
            'end_time' => $endTime,
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
            if (isset($item['create_time'])) {
                $item['createTime'] = date('H:i', $item['create_time']);
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

    /**
     * 提交任务
     * @param $userId
     * @param $taskId
     * @param $images
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @auhtor wenzhen-chen
     * @time 2019-9-2
     */
    public static function submitTask($userId, $taskId, $images)
    {
        //1、检测任务是否存在
        $where = 'user_id=' . $userId . ' and task_id=' . $taskId . ' and status=' . self::STATUS_WAIT;
        $logModel = new TaskLog();
        $taskInfo = $logModel->getInfo($where);
        if (!empty($taskInfo)) {
            //2、检测任务是否超时
            if ($taskInfo['end_time'] > time()) {
                //3、修改任务
                $updateData['images'] = $images;
                $updateData['status'] = self::STATUS_SUBMIT;
                try {
                    Db::startTrans();
                    $where = 'user_id=' . $userId . ' and task_id=' . $taskId;
                    $logModel->updateInfo($where, $updateData);
                    Db::commit();
                    $code = 0;
                    $msg = '提交成功';
                } catch (\Exception $e) {
                    Db::rollback();
                    $code = -3;
                    $msg = '提交失败';
                }
            } else {
                $code = -2;
                $msg = '任务已超时';
            }

        } else {
            $code = -1;
            $msg = '任务不存在';
        }
        return [
            'code' => $code,
            'msg' => $msg,
        ];
    }
}