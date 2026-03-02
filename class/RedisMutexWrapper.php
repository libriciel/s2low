<?php

namespace S2lowLegacy\Class;

use Malkusch\Lock\Mutex\Mutex;
use Malkusch\Lock\Mutex\RedisMutex;
use Redis;

class RedisMutexWrapper
{
    /** @var int on mets 2* le TTR de la réponse beanstalked */
    private const DEFAULT_TIMEOUT = 60 * 5;
    private ?Redis $redisInstance = null;

    public function __construct(
        private readonly string $redis_server,
        private readonly int $redis_port
    )
    {
    }

    public function getMutex(string $mutex_name, int $timeout = self::DEFAULT_TIMEOUT) : Mutex
    {
        $redis = $this->getRedisInstance();
        return new RedisMutex(
            $redis,
            $mutex_name,
            $timeout
        );
    }

    private function getRedisInstance() : Redis
    {
        if (is_null($this->redisInstance)) {
            $this->redisInstance = new Redis();
            $this->redisInstance->connect($this->redis_server, $this->redis_port);
        }
        return $this->redisInstance;
    }
}
