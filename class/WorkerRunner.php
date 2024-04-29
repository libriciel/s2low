<?php

declare(strict_types=1);

namespace S2lowLegacy\Class;

interface WorkerRunner
{
    public function work(): bool;
}
