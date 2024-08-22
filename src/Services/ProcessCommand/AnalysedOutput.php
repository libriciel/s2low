<?php

namespace S2low\Services\ProcessCommand;

class AnalysedOutput
{
    public function __construct(
        private readonly string $result = '',
        private readonly array $blockingErrors = [],
        private readonly array $nonBlockingErrors = [],
    ) {
    }

    public function hasBlockingErrors(): bool
    {
        return !empty($this->blockingErrors);
    }

    public function getFirstBlockingErrorMessage(): string
    {
        return $this->blockingErrors[0];
    }

    public function hasNonBlockingErrors(): bool
    {
        return !empty($this->blockingErrors);
    }

    public function getNonBlockingErrors(): string
    {
        return $this->nonBlockingErrors[0];
    }

    public function getResult(): string
    {
        return $this->result;
    }
}
