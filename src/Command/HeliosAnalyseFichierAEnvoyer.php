<?php

namespace S2low\Command;

use LogicException;
use S2low\Services\Helios\HeliosAnalyseFichierAEnvoyerWorker;
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
     * @var WorkerScript
     */
    private WorkerScript $workerScript;
    /**
     * @var  HeliosAnalyseFichierAEnvoyerWorker
     */
    private HeliosAnalyseFichierAEnvoyerWorker $heliosAnalyseFichierAEnvoyer;

    /**
     * @param WorkerScript $workerScript
     * @param HeliosAnalyseFichierAEnvoyerWorker $analyseFichierAEnvoyerWorkerFactory
     */
    public function __construct(
        WorkerScript $workerScript,
        HeliosAnalyseFichierAEnvoyerWorker $analyseFichierAEnvoyerWorkerFactory
    ) {
        $this->workerScript = $workerScript;
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
        $this->workerScript->scriptWithLogs($this->heliosAnalyseFichierAEnvoyer, false);
        return 0;
    }
}
