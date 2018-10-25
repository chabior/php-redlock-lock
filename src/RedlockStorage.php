<?php

declare(strict_types = 1);

namespace chabior\Lock\Redlock;

use chabior\Lock\Exception\LockException;
use chabior\Lock\Redis\RedisStorage;
use chabior\Lock\StorageInterface;
use chabior\Lock\ValueObject\LockName;
use chabior\Lock\ValueObject\LockTimeout;
use chabior\Lock\ValueObject\LockValue;

class RedlockStorage implements StorageInterface
{
    /**
     * @var RedisStorageCollection|RedisStorage[]
     */
    private $locks;

    /**
     * @var ExceptionListenerInterface[]
     */
    private $listeners;

    /**
     * RedlockStorage constructor.
     * @param RedisStorageCollection $locks
     */
    public function __construct(RedisStorageCollection $locks)
    {
        $this->locks = $locks;
        $this->listeners = [];
    }

    public function acquire(LockName $lockName, ?LockTimeout $lockTimeout, LockValue $lockValue): void
    {
        foreach ($this->locks as $lock) {
            try {
                $lock->acquire($lockName, $lockTimeout, $lockValue);
            } catch (\Throwable $exception) {
                $this->handleException($exception);
            }
        }

        if (!$this->isLocked($lockName, $lockValue)) {
            $this->release($lockName, $lockValue);
            throw new LockException();
        }
    }

    public function release(LockName $lockName, LockValue $lockValue): void
    {
        try {
            foreach ($this->locks as $lock) {
                $lock->release($lockName, $lockValue);
            }
        } catch (\Throwable $exception) {
            $this->handleException($exception);
        }
    }

    public function isLocked(LockName $lockName, LockValue $lockValue): bool
    {
        $lockedNumber = 0;
        foreach ($this->locks as $lock) {
            try {
                if ($lock->isLocked($lockName, $lockValue)) {
                    ++$lockedNumber;
                }
            } catch (\Exception $exception) {
                $this->handleException($exception);
                continue;
            }
        }

        return $this->locks->isMajorityLocked($lockedNumber);
    }

    public function registerExceptionListener(ExceptionListenerInterface $exceptionListener): void
    {
        $this->listeners[] = $exceptionListener;
    }

    private function handleException(\Throwable $exception): void
    {
        foreach ($this->listeners as $listener) {
            $listener->handle($exception);
        }
    }
}
