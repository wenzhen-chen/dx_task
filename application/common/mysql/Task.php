<?php

namespace app\common\mysql;
class Task extends BaseMysql
{
    public $tableName = 'task';
    /**
     * 获取用户任务详情
     * @param $where
     * @return array|null|\PDOStatement|string|\think\Model
     */
    public function getInfo($where)
    {
        return self::name($this->tableName)
            ->alias('task')
            ->field($this->field)
            ->join('user_info user','user.user_id=task.user_id','inner')
            ->join('task_module module','module.id=task.module_id','inner')
            ->where($where)
            ->find();
    }
}