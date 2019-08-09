<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/16
 * Time: 19:36
 */

namespace app\common\mysql;


use think\Model;

class BaseMysql extends Model
{
    public $tableName = '';
    public $field = '';
    public $order = '';

    /**
     * 获取详情
     * @param $where
     * @return array|\PDOStatement|string|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getInfo($where)
    {
        return self::name($this->tableName)
            ->field($this->field)
            ->where($where)
            ->find();
    }

    /**
     * 查询列表
     * @param $where
     * @param $offset
     * @param $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @auhtor wenzhen-chen
     * @time 2019-7-16
     */
    public function getList($where, $offset, $limit)
    {
        return self::name($this->tableName)
            ->field($this->field)
            ->where($where)
            ->limit($offset, $limit)
            ->order($this->order)
            ->select()
            ->toArray();
    }

    /**
     * 修改数据
     * @param $where
     * @param $data
     * @return int|string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * @auhtor wenzhen-chen
     * @time 2019-7-17
     */
    public function updateInfo($where, $data)
    {
        return self::name($this->tableName)
            ->where($where)
            ->update($data);
    }

    /**
     * 添加数据
     * @param $data
     * @return int|string
     * @auhtor wenzhen-chen
     * @time 2019-7-17
     */
    public function addInfo($data)
    {
        return self::name($this->tableName)
            ->insertGetId($data);
    }

    /**
     * 批量添加
     * @param $data
     * @return int|string
     * @author wenzhen-chen
     * @time 2019-7-17
     */
    public function addList($data)
    {
        return self::name($this->tableName)
            ->insertAll($data, '', 1000);
    }

    /**
     * 删除信息
     * @param $where
     * @return int
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * @author wenzhen-chen
     * @time 2019-7-17
     */
    public function delInfo($where)
    {
        return self::name($this->tableName)
            ->where($where)
            ->delete();
    }

    /**
     * 统计数据
     * @param $where
     * @return float|string
     * @auhtor wenzhen-chen
     * @time 2019-7-17
     */
    public function countData($where)
    {
        return self::name($this->tableName)
            ->alias('log')
            ->where($where)
            ->count();
    }
}