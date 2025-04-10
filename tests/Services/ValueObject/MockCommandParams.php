<?php

namespace S2low\Tests\Services\ValueObject;


class MockCommandParams
{
    public function __construct(
        public readonly string $command,
        public readonly string|int $return
    ) {
    }
}
