<?php

namespace S2lowLegacy\Class;

use Exception;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class S2lowLogger
{
    public const MESSAGE = 'message';

    private $logger;
    private $log_level;
    private $name;

    public function __construct(Logger $logger, $log_level = Logger::INFO)
    {
        $this->logger = $logger;
        $this->log_level = $log_level;
    }

    public function debug($message, array $context = [])
    {
        $this->getLoggerWithName()->debug($message, $context);
    }

    public function info($message, array $context = [])
    {
        $this->getLoggerWithName()->info($message, $context);
    }

    public function notice($message, array $context = [])
    {
        $this->getLoggerWithName()->notice($message, $context);
    }

    public function warning($message, array $context = [])
    {
        $this->getLoggerWithName()->warning($message, $context);
    }

    public function error($message, array $context = [])
    {
        $this->getLoggerWithName()->error($message, $context);
    }

    public function alert($message, array $context = [])
    {
        $this->getLoggerWithName()->alert($message, $context);
    }

    public function critical($message, array $context = [])
    {
        $this->getLoggerWithName()->critical($message, $context);
    }

    public function emergency($message, array $context = [])
    {
        $this->getLoggerWithName()->emergency($message, $context);
    }

    public function enableStdOut($enable_stdout = true)
    {
        if (! $enable_stdout) {
            return;
        }
        try {
            $handler = new  StreamHandler('php://stdout', $this->log_level);
            $this->logger->pushHandler($handler);
        } catch (Exception $e) {
            $message =  "Impossible de créer un streamHandler sur sdtout : " . $e->getMessage();
            echo $message;
            $this->critical($message, [$e]);
        }
    }

    public function addHandler(HandlerInterface $handler)
    {
        $this->logger->pushHandler($handler);
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    private function getLoggerWithName()
    {
        if (! $this->name) {
            $trace = debug_backtrace();
            if (empty($trace[2]['class'])) {
                $this->name = basename($trace[1]['file']);
            } else {
                $this->name = $trace[2]['class'];
            }
        }
        return $this->logger->withName($this->name);
    }
}
