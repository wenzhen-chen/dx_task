<?php

namespace app\common\redis\db0;

/**
 * redis操作类
 */
class RedisAbstract
{

    /**
     * 连接的库
     * @var int
     */
    protected $_db = 0;
    static $redis = null;

    public function __construct()
    {
        return $this->getRedis();
    }

    /**
     * 获取redis连接
     *
     * @staticvar null $redis
     * @return \Redis
     * @throws \Exception
     */
    public function getRedis()
    {
        if (!self::$redis) {
            $conf = Config('redis');
            if (!$conf) {
                throw new \Exception('redis连接必须设置');
            }

            self::$redis = new \Redis();
            self::$redis->connect($conf['host'], $conf['port']);
            self::$redis->select($this->_db);
        }
        return self::$redis;
    }

}
