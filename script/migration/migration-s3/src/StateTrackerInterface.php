<?php

namespace App;

interface StateTrackerInterface
{
    public function getLastProcessedId(string $type): int;
    public function isProcessed(string $type, int $s2lowId): bool;
    public function markAsDone(string $type, int $s2lowId): void;
}
