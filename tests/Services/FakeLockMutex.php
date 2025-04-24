<?php

namespace S2low\Tests\Services;

class FakeLockMutex
{
    public function synchronized(callable $code)
    {
        return $code();
    }
}
