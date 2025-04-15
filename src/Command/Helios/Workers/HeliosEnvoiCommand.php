<?php

namespace S2low\Command\Helios\Workers;

use S2low\Services\Helios\HeliosEnvoiWorkerFactory;
use S2lowLegacy\Class\WorkerRunnerBuilder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'worker:helios-envoi',
    description: 'Envoi des flux vers la DGFiP',
)]
class HeliosEnvoiCommand extends Command
{
    private WorkerRunnerBuilder $workerBuilder;
    private HeliosEnvoiWorkerFactory $heliosEnvoiWorkerFactory;

    public function __construct(WorkerRunnerBuilder $workerRunnerBuilder, HeliosEnvoiWorkerFactory $heliosEnvoiWorkerFactory)
    {
        $this->workerBuilder = $workerRunnerBuilder;
        $this->heliosEnvoiWorkerFactory = $heliosEnvoiWorkerFactory;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'usePasstrans',
                null,
                InputOption::VALUE_NONE,
                'Doit-on utiliser Passtrans à la place de la gateway ?'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $worker = $this->workerBuilder->scriptWithLogs($this->heliosEnvoiWorkerFactory->get(
            $input->getOption('usePasstrans')
        ));
        $worker->work();
        return Command::SUCCESS;
    }
}
