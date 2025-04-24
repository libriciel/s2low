<?php

namespace S2low\Command\Actes\Workers;

use S2lowLegacy\Class\actes\ActesAntivirusWorker;
use S2lowLegacy\Class\WorkerRunnerBuilder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'worker:acte-antivirus',
    description: 'Analyse les fichiers des transactions au status \'CREE\'.',
)]
class ActeAntivirusCommand extends Command
{
    public function __construct(
        private readonly ActesAntivirusWorker $worker,
        private readonly WorkerRunnerBuilder $workerRunnerBuilder
    ) {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->workerRunnerBuilder->scriptWithLogs($this->worker)->work();
        return Command::SUCCESS;
    }
}
