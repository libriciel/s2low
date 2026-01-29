<?php

namespace S2low\DTO;

use Throwable;

class PadesValidationResult
{
    public function __construct(
        public bool $isSigned,
        public bool $isValid,
        public bool $connectionError,
        public string $message,
        public ?string $lastResult,
        public ?Throwable $exception = null,
    ) {
    }
}
