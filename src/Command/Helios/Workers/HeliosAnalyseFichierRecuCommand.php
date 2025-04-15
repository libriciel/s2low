<?php

namespace S2low\Command\Helios\Workers;

use S2lowLegacy\Class\helios\HeliosAnalyseFichierRecuWorker;
use S2lowLegacy\Class\WorkerRunnerBuilder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'worker:helios-analyse-fichier-recu',
    description: 'Analyse les fichiers recu de la DGFIP'
)]
class HeliosAnalyseFichierRecuCommand extends Command
{
    public function __construct(
        private readonly WorkerRunnerBuilder $workerBuilder,
        private readonly HeliosAnalyseFichierRecuWorker $worker
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->workerBuilder->scriptWithLogs($this->worker)
            ->work();
        return Command::SUCCESS;
    }
}
