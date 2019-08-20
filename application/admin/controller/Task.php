<?php
namespace app\admin\controller;

use app\home\controller\Common;
use think\facade\Request;
use app\common\business\Task as taskBn;

class Task extends Common
{
    /**
     * 任务分类列表
     * @return mixed
     */
    public function moduleList()
    {
        if (Request::isAjax()) {
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');
            $data = taskBn::getModuleList([], $page, $pageSize);
            return [
                'code' => 0,
                'msg' => '获取成功!',
                'data' => $data['list'],
                'count' => $data['total'],
                'rel' => 1
            ];
        }
        return $this->fetch();
    }
}