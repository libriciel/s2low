<?php

namespace S2low\Command\Actes\Workers;

use Symfony\Component\Console\Attribute\AsCommand;
use S2lowLegacy\Class\actes\ActesAnalyseFichierAEnvoyerWorker;
use S2lowLegacy\Class\WorkerRunnerBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'worker:acte-analyse-fichier-a-envoyer',
    description: 'Analyse les fichiers de l\'archive à envoyer.'
)]
class ActesAnalyseFichierAEnvoyerCommand extends Command
{
    public function __construct(
        private readonly ActesAnalyseFichierAEnvoyerWorker $worker,
        private readonly WorkerRunnerBuilder $workerRunnerBuilder)
    {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->workerRunnerBuilder->scriptWithLogs($this->worker)->work();
        return Command::SUCCESS;
    }
}
