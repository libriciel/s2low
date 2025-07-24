<?php

namespace S2lowLegacy\Class;

use S2lowLegacy\Class\actes\ActesEnvoiFichierWorker;

class WorkersDelayRetry
{
    public const DEFAULT_DELAY_RETRY_IN_SECONDS = 60;
    private const DELAY_MAP = [
        ActesEnvoiFichierWorker::class => 300,
    ];

    public static function get(IWorker $worker): int
    {
        return self::DELAY_MAP[get_class($worker)] ?? self::DEFAULT_DELAY_RETRY_IN_SECONDS;
    }
}
