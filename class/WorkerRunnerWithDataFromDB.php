<?php

namespace S2lowLegacy\Class;

use Exception;
use S2lowLegacy\Lib\PausingQueueException;
use S2lowLegacy\Lib\SigTermHandler;

class WorkerRunnerWithDataFromDB
{
    /**
     * @var \S2lowLegacy\Class\IWorker
     */
    private IWorker $worker;

    /**
     * @var \S2lowLegacy\Class\S2lowLogger
     */
    private S2lowLogger $s2lowLogger;
    private mixed $min_execution_time_in_seconds;
    /**
     * @var \S2lowLegacy\Lib\SigTermHandler
     */
    private SigTermHandler $sigTermHandler;

    public function __construct(
        IWorker $worker,
        S2lowLogger $s2lowLogger,
        SigTermHandler $sigTermHandler,
        int $min_execution_time_in_seconds
    ) {
        $this->worker = $worker;
        $this->s2lowLogger = $s2lowLogger;
        $this->sigTermHandler = $sigTermHandler;
        $this->min_execution_time_in_seconds = $min_execution_time_in_seconds;
    }
    public function work()
    {
        $start = time();

        $this->s2lowLogger->info("Démarrage en mode supervisord");

        try {
            $this->checkAll();
        } catch (WorkerScriptException $e) {
            $this->s2lowLogger->notice($e->getMessage());
            return true;
        } catch (PausingQueueException $e) {
            $seconds = $e->getTimeToWait();
            $this->s2lowLogger->info("Pausing queue for $seconds seconds");
            sleep($seconds);
        } catch (Exception $e) {
            $message = $e->getMessage();
            $this->s2lowLogger->critical(
                "Erreur lors de l'execution du script : " . $message,
                [$e->getTraceAsString()]
            );
            return false;
        }

        $sleep = $this->min_execution_time_in_seconds - (time() - $start);
        if ($sleep > 0) {
            $this->s2lowLogger->debug("Arret du script $sleep secondes");
            sleep_wrapper($sleep);
        }
        return true;
    }


    public function setMinExecutionTimeInSeconds($min_execution_time_in_seconds)
    {
        $this->min_execution_time_in_seconds = $min_execution_time_in_seconds;
    }

    /**
     * @throws \S2lowLegacy\Class\CloudStorageException
     * @throws \S2lowLegacy\Class\WorkerScriptException
     * @throws \S2lowLegacy\Lib\PausingQueueException
     * @throws \S2lowLegacy\Lib\UnrecoverableException
     */
    private function checkAll()
    {
        $id_list = $this->worker->getAllId();
        $this->s2lowLogger->info(count($id_list) . " travaux trouvées");

        foreach ($id_list as $id) {
            if ($this->sigTermHandler->isSigtermCalled()) {
                throw new WorkerScriptException("SIGTERM reçu");
            }
            $data = $this->worker->getData($id);
            try {
                if ($this->worker->isDataValid($data)) {
                    $this->worker->work($data);
                } else {
                    $this->s2lowLogger->info("Le travail n'est plus à faire, abandon", [$data]);
                }
            } catch (RecoverableException $e) {
                /* Nothing to do*/
            }
        }
    }
}
