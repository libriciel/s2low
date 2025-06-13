<?php

namespace S2low\Command\Actes\Workers;

use Symfony\Component\Console\Attribute\AsCommand;
use S2lowLegacy\Class\actes\ActesEnvoiFichierWorker;
use S2lowLegacy\Class\WorkerRunnerBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'worker:acte-envoi-fichier',
    description: 'Envoi les fichiers à la DGCL.',
)]
class ActesEnvoiFichierCommand extends Command
{
    public function __construct(
        private readonly ActesEnvoiFichierWorker $worker,
        private readonly WorkerRunnerBuilder $workerRunnerBuilder
    ) {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {

        var_dump('Demarrage de la commande.');
        $this->workerRunnerBuilder->scriptWithLogs($this->worker)->work();
        return Command::SUCCESS;
    }
}
