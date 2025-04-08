<?php

namespace S2low\Command\Helios\Workers;

use S2lowLegacy\Class\helios\HeliosAnalyseFichierRecuWorker;
use S2lowLegacy\Class\WorkerRunnerBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class HeliosAnalyseFichierRecuCommand extends Command
{
    private WorkerRunnerBuilder $workerBuilder;
    private HeliosAnalyseFichierRecuWorker $worker;

    public function __construct(
        WorkerRunnerBuilder $workerRunnerBuilder,
        HeliosAnalyseFichierRecuWorker $worker
    ) {
        $this->workerBuilder = $workerRunnerBuilder;
        $this->worker = $worker;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('helios:analyse-fichier-recu')
            ->setDescription(
                "Analyse les fichiers recu de la DGFIP"
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->workerBuilder->scriptWithLogs($this->worker)
            ->work();
        return Command::SUCCESS;
    }

}
