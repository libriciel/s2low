<?php

namespace S2low\Command\Actes\Workers;

use S2lowLegacy\Class\actes\ActesReceptionFichierWorker;
use S2lowLegacy\Class\JobFetcherFromDB;
use S2lowLegacy\Class\WorkerRunnerBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ActesReceptionFichierCommand extends Command
{
    private ActesReceptionFichierWorker $worker;
    private WorkerRunnerBuilder $workerRunnerBuilder;

    public function __construct(
        ActesReceptionFichierWorker $worker,
        WorkerRunnerBuilder $workerRunnerBuilder
    ) {
        parent::__construct();
        $this->worker = $worker;
        $this->workerRunnerBuilder = $workerRunnerBuilder;
    }

    protected function configure(): void
    {
        $this
            ->setName('actes:reception-fichier')
            ->setDescription(
                "Reception des réponses de la DGCL."
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $workerRunner = $this->workerRunnerBuilder->scriptWithLogs($this->worker, true, JobFetcherFromDB::class);
        $workerRunner->setMinExecutionTimeInSeconds(10);
        $workerRunner->work();

        return Command::SUCCESS;
    }
}
