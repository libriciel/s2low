<?php

namespace S2low\Logger;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class ServiceNameProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly string $serviceName
    ) {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(extra: [
            ...$record->extra,
            'service_name' => $this->serviceName,
        ]);
    }
}
