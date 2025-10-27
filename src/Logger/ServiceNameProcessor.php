<?php

namespace S2low\Logger;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class ServiceNameProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(extra: [
            ...$record->extra,
            'service_name' => $this->getServiceName(),
        ]);
    }

    private function getServiceName(): string
    {
        return isset($_SERVER['argv']) ? $this->getCommandName() ?? $this->getLegacyScriptName() : 'app';
    }

    private function getCommandName(): ?string
    {
        return $_SERVER['argv'][1] ?? null;
    }

    private function getLegacyScriptName(): string
    {
        return substr(basename($_SERVER['argv'][0]), 0, -4);
    }
}
