<?php

namespace S2low\Command\Actes\Workers;

use S2lowLegacy\Class\actes\ActesEnvoiFichierWorker;
use S2lowLegacy\Class\WorkerRunnerBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ActesEnvoiFichierCommand extends Command
{
    private ActesEnvoiFichierWorker $worker;
    private WorkerRunnerBuilder $workerRunnerBuilder;

    public function __construct(ActesEnvoiFichierWorker $worker, WorkerRunnerBuilder $workerRunnerBuilder)
    {
        parent::__construct();
        $this->worker = $worker;
        $this->workerRunnerBuilder = $workerRunnerBuilder;
    }

    protected function configure(): void
    {
        $this
            ->setName('actes:actes-envoi-fichier')
            ->setDescription(
                "Envoi les fichiers à la DGCL."
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->workerRunnerBuilder->scriptWithLogs($this->worker)->work();
        return Command::SUCCESS;
    }
}
