<?php

namespace S2low\Command;

use LogicException;
use S2lowLegacy\Class\helios\HeliosAnalyseFichierAEnvoyerWorker;
use S2lowLegacy\Class\WorkerScript;
use Symfony\Component\Console\Command\Command;

/**
 *
 */
class HeliosAnalyseFichierAEnvoyer extends Command
{
    /**
     * @var \S2lowLegacy\Class\WorkerScript
     */
    private WorkerScript $workerScript;
    /**
     * @var \S2lowLegacy\Class\helios\HeliosAnalyseFichierAEnvoyerWorker
     */
    private HeliosAnalyseFichierAEnvoyerWorker $heliosAnalyseFichierAEnvoyer;

    /**
     * @param \S2lowLegacy\Class\WorkerScript $workerScript
     * @param \S2lowLegacy\Class\helios\HeliosEnvoiWorker $heliosEnvoiWorker
     */
    public function __construct(WorkerScript $workerScript, HeliosAnalyseFichierAEnvoyerWorker $analyseFichierAEnvoyerWorker)
    {
        $this->workerScript = $workerScript;
        $this->heliosAnalyseFichierAEnvoyer = $analyseFichierAEnvoyerWorker;
        parent::__construct();
    }

    /**
     * Configures the current command.
     */
    protected function configure()
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
    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output): int
    {
        $this->workerScript->scriptWithLogs($this->heliosAnalyseFichierAEnvoyer);
    }
}
