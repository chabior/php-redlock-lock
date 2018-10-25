<?php

declare(strict_types = 1);

namespace chabior\Lock\Redlock;

use chabior\Lock\Redis\RedisStorage;

class RedisStorageCollection implements \IteratorAggregate
{
    private const MIN_STORAGE = 3;

    /**
     * @var RedisStorage[]
     */
    private $locks;

    public function __construct(array $locks)
    {
        if (count($locks) < self::MIN_STORAGE) {
            throw new \InvalidArgumentException('Redlock storage can be used with min 3 redis storage!');
        }

        array_walk($locks, function ($redisStorage) {
            if (!$redisStorage instanceof RedisStorage) {
                throw new \InvalidArgumentException('Only RedisStorage can be used with redlock storage');
            }
        });

        $this->locks = $locks;
    }

    public function isMajorityLocked(int $locked): bool
    {
        return $locked >= ceil(count($this->locks) / 2);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->locks);
    }
}
