<?php

use malkusch\lock\mutex\PHPRedisMutex;

class RedisMutexWrapper
{
    /** @var int on mets 2* le TTR de la réponse beanstalked */
    private const DEFAULT_TIMEOUT = 120;

    private $redis_server;
    private $redis_port;

    private $redisInstance;

    public function __construct($redis_server, $redis_port)
    {
        $this->redis_server = $redis_server;
        $this->redis_port = $redis_port;
    }

    public function getMutex($mutex_name, $timeout = self::DEFAULT_TIMEOUT)
    {
        $redis = $this->getRedisInstance();
        return new PHPRedisMutex([$redis], $mutex_name, $timeout);
    }

    public function isMutexInUsed($mutex_name)
    {
        $redis = $this->getRedisInstance();
        return (bool)$redis->get(sprintf('lock_%s', $mutex_name));
    }

    private function getRedisInstance()
    {
        if (! $this->redisInstance) {
            $this->redisInstance = new Redis();
            $this->redisInstance->connect($this->redis_server, $this->redis_port);
        }
        return $this->redisInstance;
    }
}
