<?php

namespace S2low\Command\Actes\Workers;

use S2lowLegacy\Class\actes\ActesPrepareSaeWorker;
use S2lowLegacy\Class\JobFetcherFromDB;
use S2lowLegacy\Class\WorkerRunnerBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ActesPrepareSaeCommand extends Command
{
    private ActesPrepareSaeWorker $worker;
    private WorkerRunnerBuilder $workerRunnerBuilder;

    public function __construct(ActesPrepareSaeWorker $worker, WorkerRunnerBuilder $workerRunnerBuilder)
    {
        parent::__construct();
        $this->worker = $worker;
        $this->workerRunnerBuilder = $workerRunnerBuilder;
    }

    protected function configure(): void
    {
        $this
            ->setName('actes:prepare-sae')
            ->setDescription(
                "Prepare l'acte pour l'envoi au SAE."
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->workerRunnerBuilder->scriptWithLogs(
            $this->worker,
            true,
            JobFetcherFromDB::class
        )->work();

        return Command::SUCCESS;
    }
}
