<?php

namespace S2low\Command\Actes\Workers;

use S2lowLegacy\Class\actes\ActesAnalyseFichierAEnvoyerWorker;
use S2lowLegacy\Class\WorkerRunnerBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ActesAnalyseFichierAEnvoyerCommand extends Command
{
    private ActesAnalyseFichierAEnvoyerWorker $worker;
    private WorkerRunnerBuilder $workerRunnerBuilder;

    public function __construct(ActesAnalyseFichierAEnvoyerWorker $worker, WorkerRunnerBuilder $workerRunnerBuilder)
    {
        parent::__construct();
        $this->worker = $worker;
        $this->workerRunnerBuilder = $workerRunnerBuilder;
    }

    protected function configure(): void
    {
        $this
            ->setName('actes:analyse-fichier-a-envoyer')
            ->setDescription(
                "Analyse les fichiers de l'archive à envoyer."
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->workerRunnerBuilder->scriptWithLogs($this->worker)->work();
        return Command::SUCCESS;
    }
}
