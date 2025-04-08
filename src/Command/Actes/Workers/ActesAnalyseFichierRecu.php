<?php

namespace S2low\Command\Actes\Workers;

use S2lowLegacy\Class\actes\ActesAnalyseFichierRecuWorker;
use S2lowLegacy\Class\JobFetcherFromDB;
use S2lowLegacy\Class\WorkerRunnerBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ActesAnalyseFichierRecu extends Command
{
    private ActesAnalyseFichierRecuWorker $worker;
    private WorkerRunnerBuilder $workerRunnerBuilder;

    public function __construct(
        ActesAnalyseFichierRecuWorker $worker,
        WorkerRunnerBuilder $workerRunnerBuilder
    ) {
        parent::__construct();
        $this->worker = $worker;
        $this->workerRunnerBuilder = $workerRunnerBuilder;
    }

    protected function configure(): void
    {
        $this
            ->setName('actes:analyse-fichier-recu')
            ->setDescription(
                "Analyse la réponse de la DGCL."
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $workerRunner = $this->workerRunnerBuilder->scriptWithLogs($this->worker, true, JobFetcherFromDB::class);
        $workerRunner->work();

        return Command::SUCCESS;
    }
}
