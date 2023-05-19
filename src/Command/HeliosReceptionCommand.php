<?php

namespace S2low\Command;

use LogicException;
use S2low\Services\Helios\HeliosReceptionWorkerFactory;
use S2lowLegacy\Class\WorkerScript;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *
 */
class HeliosReceptionCommand extends Command
{
    /**
     * @var WorkerScript
     */
    private WorkerScript $workerScript;
    /**
     * @var HeliosReceptionWorkerFactory
     */
    private HeliosReceptionWorkerFactory $heliosReceptionWorkerFactory;

    /**
     * @param WorkerScript $workerScript
     * @param HeliosReceptionWorkerFactory $heliosEnvoiWorkerFactory
     */
    public function __construct(WorkerScript $workerScript, HeliosReceptionWorkerFactory $heliosEnvoiWorkerFactory)
    {
        $this->workerScript = $workerScript;
        $this->heliosReceptionWorkerFactory = $heliosEnvoiWorkerFactory;
        parent::__construct();
    }

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('cron:helios-reception')
            ->setDescription(
                "Reception des flux vers la DGFiP"
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
        $this->workerScript->setMinExecutionTimeInSeconds(240);
        $this->workerScript->scriptWithLogs(
            $this->heliosReceptionWorkerFactory->get($input->getOption('usePasstrans')),
            false,
            true
        );

        return 0;
    }
}
