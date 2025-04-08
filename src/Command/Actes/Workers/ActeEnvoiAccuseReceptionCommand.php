<?php

namespace S2low\Command\Actes\Workers;

use S2lowLegacy\Class\actes\ActesEnvoiAR;
use S2lowLegacy\Class\S2lowLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ActeEnvoiAccuseReceptionCommand extends Command
{
    private S2lowLogger $logger;
    private ActesEnvoiAR $actesEnvoiAR;

    public function __construct(
        S2lowLogger $logger,
        ActesEnvoiAR $actesEnvoiAR,
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->actesEnvoiAR = $actesEnvoiAR;
    }

    protected function configure(): void
    {
        $this
            ->setName('actes:envoi-ar')
            ->setDescription(
                ""
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->setName("actes-envoi-ar");
        $this->logger->enableStdOut(true);

        $start = time();
        $this->logger->info("Debut " . date("Y-m-d H:i:s", $start));
        $min_exec_time = 10;

        try {
            $this->actesEnvoiAR->sendAllAR();
        } catch (\Exception $e) {
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

        return Command::SUCCESS;
    }
}
