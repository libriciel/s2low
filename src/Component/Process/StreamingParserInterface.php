<?php

namespace S2low\Component\Process;

interface StreamingParserInterface
{
    public function parseLine(string $line): void;

    public function isFinished(): bool;

    public function getResult(): mixed;
}
