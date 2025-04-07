<?php

namespace S2low\Command\Actes\Workers;

use S2lowLegacy\Class\actes\ActesAntivirusWorker;
use S2lowLegacy\Class\WorkerRunnerBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ActeAntivirusCommand extends Command
{
    private ActesAntivirusWorker $worker;
    private WorkerRunnerBuilder $workerRunnerBuilder;

    public function __construct(ActesAntivirusWorker $worker, WorkerRunnerBuilder $workerRunnerBuilder)
    {
        parent::__construct();
        $this->worker = $worker;
        $this->workerRunnerBuilder = $workerRunnerBuilder;
    }

    protected function configure(): void
    {
        $this
            ->setName('actes:actes-antivirus')
            ->setDescription(
                "Analyse les fichiers des transactions au status 'CREE'."
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->workerRunnerBuilder->scriptWithLogs($this->worker)->work();
        return Command::SUCCESS;
    }
}
