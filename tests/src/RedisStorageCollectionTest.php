<?php

declare(strict_types = 1);

namespace chabior\Lock\Redlock\Tests;

use chabior\Lock\Redis\Client\Config;
use chabior\Lock\Redis\Client\PHPRedisClient;
use chabior\Lock\Redis\RedisStorage;
use chabior\Lock\Redlock\RedisStorageCollection;
use PHPUnit\Framework\TestCase;

class RedisStorageCollectionTest extends TestCase
{
    public function testCreate(): void
    {
        new RedisStorageCollection([
            new RedisStorage(new PHPRedisClient(Config::fromDefaults(), new \Redis())),
            new RedisStorage(new PHPRedisClient(Config::fromDefaults(), new \Redis())),
            new RedisStorage(new PHPRedisClient(Config::fromDefaults(), new \Redis()))
        ]);

        $this::assertTrue(true);
    }

    public function testCreateWithLowerNumberOfStorages(): void
    {
        $this::expectException(\InvalidArgumentException::class);

        new RedisStorageCollection([
            new RedisStorage(new PHPRedisClient(Config::fromDefaults(), new \Redis())),
            new RedisStorage(new PHPRedisClient(Config::fromDefaults(), new \Redis())),
        ]);
    }

    public function testWithWrongStorage(): void
    {
        $this::expectException(\InvalidArgumentException::class);

        new RedisStorageCollection([
            new RedisStorage(new PHPRedisClient(Config::fromDefaults(), new \Redis())),
            new RedisStorage(new PHPRedisClient(Config::fromDefaults(), new \Redis())),
            Config::fromDefaults()
        ]);
    }

    /**
     * @param int $storages
     * @param int $locked
     * @param bool $isLocked
     *
     * @dataProvider dataProviderTestIsMajorityLocked
     */
    public function testIsMajorityLocked(int $storages, int $locked, bool $isLocked): void
    {
        $collection = [];
        foreach (range(0, $storages) as $i) {
            $collection[] = new RedisStorage(new PHPRedisClient(Config::fromDefaults(), new \Redis()));
        }

        $storage = new RedisStorageCollection($collection);

        $this::assertSame($isLocked, $storage->isMajorityLocked($locked));
    }

    public function dataProviderTestIsMajorityLocked()
    {
        return [
            [3, 3, true],
            [3, 2, true],
            [3, 1, false],
            [3, 0, false],
            [4, 1, false],
            [4, 2, false],
            [4, 3, true],
            [4, 4, true],
        ];
    }
}
