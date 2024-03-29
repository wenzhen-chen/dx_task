<?php

namespace app\common\redis\db0;

/**
 * 任务缓存
 */
class TaskCache extends RedisAbstract
{
    /**
     * 添加任务缓存
     * @param $price
     * @param $taskId
     * @param $surplus
     * @throws \Exception
     */
    public function addTask($price, $taskId, $surplus)
    {
        //1、添加任务序列 按佣金金额
        $this->getRedis()->zAdd('task_sort', $price, $taskId);
        $this->getRedis()->set('task_surplus:' . $taskId, $surplus);
    }

    /**
     * 获取最高任务余量与id
     * @param $userId
     * @return array
     * @throws \Exception
     */
    public function getMaxTask($userId)
    {
        $maxId = 0;
        $surplus = 0;
        //1、获取排名信息
        $sizeTask = $this->getRedis()->zSize('task_sort');
        for ($i = 0; $i < $sizeTask; $i++) {
            $taskId = $this->getTaskByRank($i);
            $is_exist = $this->getRedis()->sIsMember('user_task_storage:' . $userId, $taskId);
            if (!$is_exist) {
                $maxId = $taskId;
                break;
            }
        }
        if ($maxId) {
            //2、获取余量
            $surplus = $this->getRedis()->get('task_surplus:' . $maxId);
            //3、扣除余量
            $this->getRedis()->decr('task_surplus:' . $maxId);
            //4、写入用户任务库
            $this->addUserTaskStorage($userId, $maxId);
            //5、写入用户进行中的任务
            $this->addUserCurrentTask($userId, $maxId, 300);
        }

        return [
            'taskId' => $maxId,
            'surplus' => $surplus
        ];
    }

    /**
     * 根据排名获取任务信息
     * @param $rank
     * @return mixed
     * @throws \Exception
     */
    public function getTaskByRank($rank)
    {
        $taskInfo = $this->getRedis()->zRange('task_sort', $rank, $rank);
        $taskId = $taskInfo[0];
        return $taskId;
    }

    /**
     * 写入用户任务库
     * @param $userId
     * @param $taskId
     * @throws \Exception
     */
    public function addUserTaskStorage($userId, $taskId)
    {
        $this->getRedis()->sAdd('user_task_storage:' . $userId, $taskId);
    }

    /**
     * 添加用户进行中的任务
     * @param $userId
     * @param $taskId
     * @param $countdown //任务有效时间
     * @throws \Exception
     */
    public function addUserCurrentTask($userId, $taskId, $countdown)
    {
        $info = json_encode([
            'taskId' => $taskId,
            'endTime' => time() + $countdown
        ]);
        $this->getRedis()->set('current_task:' . $userId, $info);
    }

    /**
     * 获取用户进行中的任务
     * @param $userId
     * @return bool|string
     * @throws \Exception
     */
    public function getUserCurrentTask($userId)
    {
        $info = $this->getRedis()->get('current_task:' . $userId);
        return json_decode($info,true);
    }
}
