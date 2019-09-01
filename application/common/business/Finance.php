<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/9/1
 * Time: 15:00
 */

namespace app\common\business;


use app\common\mysql\DouJinLog;
use app\common\mysql\ScoreLog;
use app\common\mysql\UserInfo;

class Finance
{
    const OPT_ADD = 'add';//加操作
    const OPT_SUB = 'sub';//减操作
    /**
     *
     * 抖金余额操作，未开事务并且无返回值，请在各自流程中操作
     * @param $opt //操作 add为增，sub为减
     * @param $userId //用户id
     * @param $douJin //操作金额
     * @param $post
     *          order_id //订单号，如不是订单操作，则存对应操作id
     *          type //类型 1、充值 2、发布任务 3、完成任务 4、任务退回
     *          remark 备注
     *          balance //余额
     * @throws \think\Exception
     */
    public static function updateUserDouJin($opt, $userId, $douJin, $post)
    {
        $douJin = $opt == self::OPT_ADD ? $douJin : -$douJin;
        //1、修改用户抖金金额
        $userModel = new UserInfo();
        $userModel->updateDouJin($userId, $douJin);
        //2、新增抖金日志
        $logModel = new DouJinLog();
        $logData['add_time'] = time();
        $logData['user_id'] = $userId;
        $logData['order_id'] = $post['order_id'];
        $logData['doujin'] = $douJin;//判断增减操作
        $logData['type'] = $post['type'];
        $logData['remark'] = $post['remark'];
        $logData['balance'] = $post['balance'];
        $logModel->addInfo($logData);
    }

    /**
     * 积分操作操作，未开事务并且无返回值，请在各自流程中操作
     * @param $opt $opt //操作 add为增，sub为减
     * @param $userId //用户id
     * @param $score //操作积分
     * @param $post
     *          type //类型 1、注册 2、签到 3、发布任务 4、完成任务
     *          remark 备注
     * @throws \think\Exception
     */
    public static function updateUserScore($opt, $userId, $score, $post)
    {
        $score = $opt == self::OPT_ADD ? $score : -$score;
        //1、修改用户积分
        $userModel = new UserInfo();
        $userModel->updateScore($userId, $score);
        //2、新增用户积分日志
        $logModel = new ScoreLog();
        $logData['create_time'] = time();
        $logData['user_id'] = $userId;
        $logData['order_id'] = $post['order_id'];
        $logData['score'] = $score;
        $logData['type'] = $post['type'];
        $logData['remark'] = $post['remark'];
        $logModel->addInfo($logData);
    }
}