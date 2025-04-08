<?php

namespace S2low\Command\Helios\Workers;

use S2low\Services\Helios\HeliosReceptionWorkerFactory;
use S2lowLegacy\Class\WorkerRunnerBuilder;
use S2lowLegacy\Class\JobFetcherFromSelfUpdatedBeanstalkd;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class HeliosReceptionCommand extends Command
{
    private WorkerRunnerBuilder $workerRunnerBuilder;
    private HeliosReceptionWorkerFactory $heliosReceptionWorkerFactory;

    public function __construct(
        WorkerRunnerBuilder $workerRunnerBuilder,
        HeliosReceptionWorkerFactory $heliosEnvoiWorkerFactory
    ) {
        $this->workerRunnerBuilder = $workerRunnerBuilder;
        $this->heliosReceptionWorkerFactory = $heliosEnvoiWorkerFactory;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('helios:reception')
            ->setDescription(
                "Reception des flux de la DGFiP"
            )
            ->addOption(
                'usePasstrans',
                null,
                InputOption::VALUE_NONE,
                'Doit-on utiliser Passtrans à la place de la gateway ?'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $worker = $this->workerRunnerBuilder->scriptWithLogs(
            $this->heliosReceptionWorkerFactory->get($input->getOption('usePasstrans')),
            false,
            JobFetcherFromSelfUpdatedBeanstalkd::class
        );

        $worker->setMinExecutionTimeInSeconds(240);
        $worker->work();
        return Command::SUCCESS;
    }
}
