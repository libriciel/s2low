<?php

namespace S2low\Logger;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class MailerProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(extra: [
            ...$record->extra,
            'pid' => getmypid(),
        ]);
    }
}
