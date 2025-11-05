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
        $serviceName = 'app';

        if ($this->isNotExecutedByWebServer()) {
            if ($this->isAlonePhpScript()) {
                $serviceName = $this->getLegacyScriptName();
            } else {
                $serviceName = $this->getSymphonyCommandName();
            }
        }

        return $serviceName;
    }

    private function getSymphonyCommandName(): ?string
    {
        return $_SERVER['argv'][1] ?? 'service-name-undefined';
    }

    private function getLegacyScriptName(): string
    {
        $res = 'service-name-undefined';
        if (isset($_SERVER['argv'][0])) {
            $res = substr(basename($_SERVER['argv'][0]), 0, -4);
        }

        return $res;
    }

    private function isAlonePhpScript(): bool
    {
        return str_ends_with($_SERVER['SCRIPT_FILENAME'], '.php');
    }

    /**
     * @return bool
     */
    public function isNotExecutedByWebServer(): bool
    {
        return isset($_SERVER['argv']);
    }
}
