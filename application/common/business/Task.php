<?php

namespace app\common\business;

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
                $redis->addTask($post['price'],$newId,$post['number']);
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
     * @return array
     */
    public static function getMaxGroupTask($userId){
        $code = -1;
        $msg = '无空闲任务';
        $taskInfo = [];
        //1、查询redis,获取任务id及余量
        $redis = new TaskCache();
        $cacheInfo = $redis->getMaxTask($userId);
        if($cacheInfo['taskId']){
            $model = new taskMysql();
            $model->field = 'user.userHead,user.nikeName,module.id groupId,module.name groupName,task.title,task.id,task.desc,task.logo,task.images,task.createTime,task.price,task.number';
            $where = 'task.id='.$cacheInfo['taskId'].' and task.userId='.$userId;
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
            $taskInfo['createTime'] = date('y-m-d',$data['createTime']);
            $taskInfo['price'] = $data['price'];
            $taskInfo['remain'] = $cacheInfo['surplus'];
            $taskInfo['num'] = $data['number'];
            $taskInfo['status'] = 'pendding';//@
            $taskInfo['isHot'] = 1;//@
            $taskInfo['countdown'] = 300;//@
            $code = 0;
            $msg = '获取成功';
        }
        return [
            'code' => $code,
            'msg' => $msg,
            'data' => $taskInfo
        ];
    }
}