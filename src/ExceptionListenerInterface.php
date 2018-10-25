<?php

declare(strict_types = 1);

namespace chabior\Lock\Redlock;

interface ExceptionListenerInterface
{
    public function handle(\Throwable $exception);
}
