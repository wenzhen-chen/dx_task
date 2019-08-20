<?php
namespace app\common\business;

use app\common\mysql\TaskModule;

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
}