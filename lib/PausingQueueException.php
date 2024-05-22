<?php

namespace S2lowLegacy\Lib;

use Exception;
use Throwable;

class PausingQueueException extends Exception
{
    /**
     * Time to wait (in seconds) when this exceptions is thrown
     * @var int
     */
    public const TIMETOWAIT = 30;

    public function __construct($message = '', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getTimeToWait(): int
    {
        return self::TIMETOWAIT;
    }
}
