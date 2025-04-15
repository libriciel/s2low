<?php

namespace S2low\Command\Actes\Workers;

use S2lowLegacy\Class\actes\ActesReceptionFichierWorker;
use S2lowLegacy\Class\JobFetcherFromDB;
use S2lowLegacy\Class\WorkerRunnerBuilder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'worker:acte-reception-fichier',
    description: 'Reception des réponses de la DGCL.',
)]
class ActesReceptionFichierCommand extends Command
{
    public function __construct(
        private readonly ActesReceptionFichierWorker $worker,
        private readonly WorkerRunnerBuilder $workerRunnerBuilder
    ) {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $workerRunner = $this->workerRunnerBuilder->scriptWithLogs($this->worker, true, JobFetcherFromDB::class);
        $workerRunner->setMinExecutionTimeInSeconds(10);
        $workerRunner->work();

        return Command::SUCCESS;
    }
}
