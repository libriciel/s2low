<?php

namespace S2low\Command;

use LogicException;
use S2low\Services\Helios\HeliosEnvoiWorkerFactory;
use S2lowLegacy\Class\WorkerRunnerBuilder;
use S2lowLegacy\Class\WorkerScript;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *
 */
class HeliosEnvoiCommand extends Command
{
    /**
     * @var WorkerRunnerBuilder
     */
    private WorkerRunnerBuilder $workerBuilder;
    /**
     * @var HeliosEnvoiWorkerFactory
     */
    private HeliosEnvoiWorkerFactory $heliosEnvoiWorkerFactory;

    /**
     * @param WorkerScript $workerRunnerBuilder
     * @param HeliosEnvoiWorkerFactory $heliosEnvoiWorkerFactory
     */
    public function __construct(WorkerRunnerBuilder $workerRunnerBuilder, HeliosEnvoiWorkerFactory $heliosEnvoiWorkerFactory)
    {
        $this->workerBuilder = $workerRunnerBuilder;
        $this->heliosEnvoiWorkerFactory = $heliosEnvoiWorkerFactory;
        parent::__construct();
    }

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('cron:helios-envoi')
            ->setDescription(
                "Envoi des flux vers la DGFiP"
            )
            ->addOption(
                'usePasstrans',
                null,
                InputOption::VALUE_NONE,
                'Doit-on utiliser Passtrans à la place de la gateway ?'
            );
    }

    /**
     * Executes the current command.
     *
     * This method is not abstract because you can use this class
     * as a concrete class. In this case, instead of defining the
     * execute() method, you set the code to execute by passing
     * a Closure to the setCode() method.
     *
     * @return int 0 if everything went fine, or an exit code
     *
     * @throws LogicException When this abstract method is not implemented
     *
     * @see setCode()
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $worker = $this->workerBuilder->scriptWithLogs($this->heliosEnvoiWorkerFactory->get(
            $input->getOption('usePasstrans')
        ));
        $worker->work();
        return 0;
    }
}
