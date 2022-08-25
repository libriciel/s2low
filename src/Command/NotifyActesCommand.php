<?php

namespace S2low\Command;

use S2lowLegacy\Class\actes\ActesNotification;
use S2lowLegacy\Class\S2lowLogger;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use UnexpectedValueException;

class NotifyActesCommand extends Command
{
    private ActesNotification $actesNotification;
    private S2lowLogger $logger;

    public function __construct(ActesNotification $actesNotification, S2lowLogger $s2lowLogger)
    {
        $this->actesNotification = $actesNotification;
        $this->logger = $s2lowLogger;
        $this->logger->setName("actes-notification");
        $this->logger->enableStdOut();
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('cron:notify-actes')
            ->setDescription(
                "Notifies actes"
            )
            ->addArgument(
                'minimumExecutionTime',
                InputArgument::OPTIONAL,
                "minimum execution time",
                10
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $start = time();
        $this->logger->info("Debut " . date("Y-m-d H:i:s", $start));
        if (!is_numeric($input->getArgument('minimumExecutionTime'))) {
            throw new UnexpectedValueException("minimumExecutionTime should be an integer");
        }
        $min_exec_time = (int)$input->getArgument('minimumExecutionTime');

        try {
            $this->actesNotification->sendAutomaticNotification();
        } catch (Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->logger->critical($e->getTraceAsString());
        }

        $stop = time();
        $this->logger->info("Fin " . date("Y-m-d H:i:s", $stop));
        $sleep = $min_exec_time - ($stop - $start);
        if ($sleep > 0) {
            $this->logger->info("Arret du script : $sleep");
            sleep($sleep);
        }
        return 0;
    }
}
