<?php

declare(strict_types = 1);

namespace chabior\Lock\Redlock\Tests;

use chabior\Lock\Exception\LockException;
use chabior\Lock\Redis\Client\Config;
use chabior\Lock\Redis\Client\PHPRedisClient;
use chabior\Lock\Redis\RedisStorage;
use chabior\Lock\Redlock\ExceptionListenerInterface;
use chabior\Lock\Redlock\RedisStorageCollection;
use chabior\Lock\Redlock\RedlockStorage;
use chabior\Lock\ValueObject\LockName;
use chabior\Lock\ValueObject\LockValue;
use PHPUnit\Framework\TestCase;

class RedlockStorageTest extends TestCase
{
    public function testAcquire(): void
    {
        $collection = new RedisStorageCollection([
            new RedisStorage(new PHPRedisClient(new Config('172.17.0.2', 6379), new \Redis())),
            new RedisStorage(new PHPRedisClient(new Config('172.17.0.3', 6379), new \Redis())),
            new RedisStorage(new PHPRedisClient(new Config('172.17.0.4', 6379), new \Redis())),
        ]);
        $redlock = new RedlockStorage($collection);

        $name = new LockName(sha1(microtime()));
        $value = LockValue::fromRandomValue();
        $redlock->acquire($name, null, $value);

        $this::assertTrue($redlock->isLocked($name, $value));
    }

    public function testReleaseLock(): void
    {
        $collection = new RedisStorageCollection([
            new RedisStorage(new PHPRedisClient(new Config('172.17.0.2', 6379), new \Redis())),
            new RedisStorage(new PHPRedisClient(new Config('172.17.0.3', 6379), new \Redis())),
            new RedisStorage(new PHPRedisClient(new Config('172.17.0.4', 6379), new \Redis())),
        ]);
        $redlock = new RedlockStorage($collection);

        $name = new LockName(sha1(microtime()));
        $value = LockValue::fromRandomValue();
        $redlock->acquire($name, null, $value);
        $this::assertTrue($redlock->isLocked($name, $value));
        $redlock->release($name, $value);

        $this::assertFalse($redlock->isLocked($name, $value));
    }

    public function testFailAcquire(): void
    {
        $this::expectException(LockException::class);

        $collection = new RedisStorageCollection([
            new RedisStorage(new PHPRedisClient(new Config('172.17.0.2', 6379), new \Redis())),
            new RedisStorage(new PHPRedisClient(new Config('172.17.0.3', 16379), new \Redis())),
            new RedisStorage(new PHPRedisClient(new Config('172.17.0.4', 16379), new \Redis())),
        ]);
        $redlock = new RedlockStorage($collection);

        $name = new LockName(sha1(microtime()));
        $value = LockValue::fromRandomValue();
        $redlock->acquire($name, null, $value);
    }

    public function testAcquireInMajority(): void
    {
        $collection = new RedisStorageCollection([
            new RedisStorage(new PHPRedisClient(new Config('172.17.0.2', 6379), new \Redis())),
            new RedisStorage(new PHPRedisClient(new Config('172.17.0.3', 6379), new \Redis())),
            new RedisStorage(new PHPRedisClient(new Config('172.17.0.4', 16379), new \Redis())),
        ]);
        $redlock = new RedlockStorage($collection);

        $name = new LockName(sha1(microtime()));
        $value = LockValue::fromRandomValue();
        $redlock->acquire($name, null, $value);

        $this::assertTrue($redlock->isLocked($name, $value));
    }

    public function testExceptionHandlerIsNotified()
    {
        $collection = new RedisStorageCollection([
            new RedisStorage(new PHPRedisClient(new Config('172.17.0.2', 6379), new \Redis())),
            new RedisStorage(new PHPRedisClient(new Config('172.17.0.3', 6379), new \Redis())),
            new RedisStorage(new PHPRedisClient(new Config('172.17.0.4', 16379), new \Redis())),
        ]);
        $redlock = new RedlockStorage($collection);

        $exceptionListener = $this->createMock(ExceptionListenerInterface::class);
        $exceptionListener->expects($this::exactly(2))->method('handle');
        $redlock->registerExceptionListener($exceptionListener);

        $name = new LockName(sha1(microtime()));
        $value = LockValue::fromRandomValue();
        $redlock->acquire($name, null, $value);
    }
}
