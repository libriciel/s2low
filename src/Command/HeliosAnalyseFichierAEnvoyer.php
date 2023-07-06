<?php

namespace S2low\Command;

use LogicException;
use S2low\Services\Helios\HeliosAnalyseFichierAEnvoyerWorker;
use S2lowLegacy\Class\WorkerRunnerBuilder;
use S2lowLegacy\Class\WorkerScript;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use  Symfony\Component\Console\Output\OutputInterface;

/**
 *
 */
class HeliosAnalyseFichierAEnvoyer extends Command
{
    /**
     * @var WorkerRunnerBuilder
     */
    private WorkerRunnerBuilder $workerRunnerBuilder;
    /**
     * @var  HeliosAnalyseFichierAEnvoyerWorker
     */
    private HeliosAnalyseFichierAEnvoyerWorker $heliosAnalyseFichierAEnvoyer;

    /**
     * @param \S2lowLegacy\Class\WorkerRunnerBuilder $workerRunnerBuilder
     * @param HeliosAnalyseFichierAEnvoyerWorker $analyseFichierAEnvoyerWorkerFactory
     */
    public function __construct(
        WorkerRunnerBuilder $workerRunnerBuilder,
        HeliosAnalyseFichierAEnvoyerWorker $analyseFichierAEnvoyerWorkerFactory
    ) {
        $this->workerRunnerBuilder = $workerRunnerBuilder;
        $this->heliosAnalyseFichierAEnvoyer = $analyseFichierAEnvoyerWorkerFactory;
        parent::__construct();
    }

    /**
     * Configures the current command.
     */
    protected function configure(): void
    {
        $this
            ->setName('cron:helios-analyse-fichier-a-envoyer')
            ->setDescription(
                "Analyse des flux à envoyer à la DGFiP"
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
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     *
     * @see setCode()
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $worker = $this->workerRunnerBuilder->scriptWithLogs($this->heliosAnalyseFichierAEnvoyer, false);
        $worker->work();
        return 0;
    }
}
