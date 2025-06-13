<?php

namespace S2low\Command;

use Mtdowling\Supervisor\EventListener;
use Mtdowling\Supervisor\EventNotification;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

#[AsCommand(
    name: 'worker:process-failed',
    description: 'Log les processus qui fail.',
)]
class ProcessFailedCommand
{
    public function __construct(
        private readonly EventListener $listener,
        private readonly LoggerInterface $logger
    ) {
    }

    protected function execute(
        \Symfony\Component\Console\Input\InputInterface $input,
        \Symfony\Component\Console\Output\OutputInterface $output
    ): int {
        $this->listener->listen(function (EventListener $listener, EventNotification $event) {
            $eventData = $event->getData();
            if (isset($eventData['processname'])) {
                $processname = $eventData['processname'];
            } else {
                $processname = "unknow";
            }
            $this->logger->critical("SUPERVISORD process $processname enter FATAL state !", $eventData);
            return true;
        });

        return Command::SUCCESS;
    }
}
