<?php

namespace app\common\mysql;
class UserInfo extends BaseMysql
{
    public $tableName = 'user_info';

    /**
     * 抖金操作 自减抖金带负数，如-100
     * @param $userId
     * @param $douJin
     * @return bool
     * @throws \think\Exception
     * @author wenzhen-chen
     * @time 2019-9-1
     */
    public function updateDouJin($userId, $douJin)
    {
        return self::name($this->tableName)
            ->where('user_id', $userId)
            ->setInc('doujin', $douJin);
    }

    /**
     * 积分操作 自减带负数，如-100
     * @param $userId
     * @param $score
     * @return bool
     * @throws \think\Exception
     * @author wenzhen-chen
     * @time 2019-9-1
     */
    public function updateScore($userId, $score)
    {
        return self::name($this->tableName)
            ->where('user_id', $userId)
            ->setInc('score', $score);
    }

    /**
     * 获取用户抖金余额
     * @param $userId
     * @return mixed
     * @author wenzhen-chen
     * @time 2019-9-1
     */
    public function getUserDouJin($userId)
    {
        return self::name($this->tableName)
            ->where('user_id', $userId)
            ->value('doujin');
    }
}