<?php

namespace S2lowLegacy\Class;

use Pheanstalk\PheanstalkInterface;
use S2lowLegacy\Class\actes\ActesEnvoiFichierWorker;

class WorkersQueueProperties
{
    public const DEFAULT_DELAY_RETRY_IN_SECONDS = 60;
    private const DELAY_MAP = [
        ActesEnvoiFichierWorker::class => 300,
    ];
    private const PRIORITY_MAP = [
        ActesEnvoiFichierWorker::class => 512,
    ];

    public static function getDelay(IWorker $worker): int
    {
        return self::DELAY_MAP[get_class($worker)] ?? self::DEFAULT_DELAY_RETRY_IN_SECONDS;
    }
    public static function getPriority(IWorker $worker): int
    {
        return self::PRIORITY_MAP[get_class($worker)] ?? PheanstalkInterface::DEFAULT_PRIORITY;
    }
}
