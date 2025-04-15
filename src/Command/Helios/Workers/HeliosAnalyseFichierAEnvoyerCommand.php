<?php

namespace S2low\Command\Helios\Workers;

use S2low\Services\Helios\HeliosAnalyseFichierAEnvoyerWorker;
use S2lowLegacy\Class\WorkerRunnerBuilder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use  Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'worker:helios-analyse-fichier-a-envoyer',
    description: 'Analyse les flux à envoyer à la DGFiP'
)]
class HeliosAnalyseFichierAEnvoyerCommand extends Command
{
    private WorkerRunnerBuilder $workerRunnerBuilder;
    private HeliosAnalyseFichierAEnvoyerWorker $heliosAnalyseFichierAEnvoyer;

    public function __construct(
        WorkerRunnerBuilder $workerRunnerBuilder,
        HeliosAnalyseFichierAEnvoyerWorker $analyseFichierAEnvoyerWorkerFactory
    ) {
        $this->workerRunnerBuilder = $workerRunnerBuilder;
        $this->heliosAnalyseFichierAEnvoyer = $analyseFichierAEnvoyerWorkerFactory;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $worker = $this->workerRunnerBuilder->scriptWithLogs($this->heliosAnalyseFichierAEnvoyer, false);
        $worker->work();

        return Command::SUCCESS;
    }
}
