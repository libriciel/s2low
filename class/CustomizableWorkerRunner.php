<?php

declare(strict_types=1);

namespace S2lowLegacy\Class;

use S2lowLegacy\Lib\PausingQueueException;
use S2lowLegacy\Lib\SigTermHandler;
use Throwable;

class CustomizableWorkerRunner implements WorkerRunner
{
    private IWorker $worker;
    private S2lowLogger $s2lowLogger;
    private int $min_execution_time_in_seconds;
    private SigTermHandler $sigTermHandler;
    private JobFetchingStrategy $jobFetchingStrategies;

    public function __construct(
        IWorker $worker,
        S2lowLogger $s2lowLogger,
        SigTermHandler $sigTermHandler,
        int $min_execution_time_in_seconds,
        JobFetchingStrategy $jobFetchingStrategies
    ) {
        $this->worker = $worker;
        $this->s2lowLogger = $s2lowLogger;
        $this->sigTermHandler = $sigTermHandler;
        $this->min_execution_time_in_seconds = $min_execution_time_in_seconds;
        $this->jobFetchingStrategies = $jobFetchingStrategies;
    }
    public function work(): bool
    {
        $start = time();

        $this->s2lowLogger->info('Démarrage en mode supervisord');

        try {
            $this->worker->start();
            $this->jobFetchingStrategies->init($this->worker, $this->s2lowLogger);
            $this->checkAll();
        } catch (WorkerScriptException $e) {
            $this->s2lowLogger->notice($e->getMessage());
            return true;
        } catch (PausingQueueException $e) {
            $seconds = $e->getTimeToWait();
            $this->s2lowLogger->info("Pausing queue for $seconds seconds");
            sleep($seconds);
        } catch (Throwable $e) {
            $message = $e->getMessage();
            $this->s2lowLogger->critical(
                "Erreur lors de l'execution du script : " . $message,
                [$e->getTraceAsString()]
            );
            return false;
        } finally {
            $this->worker->end();
        }

        $sleep = $this->min_execution_time_in_seconds - (time() - $start);
        if ($sleep > 0) {
            $this->s2lowLogger->info("Arret du script $sleep secondes");
            sleep_wrapper($sleep);
        }
        return true;
    }


    public function setMinExecutionTimeInSeconds($min_execution_time_in_seconds): void
    {
        $this->min_execution_time_in_seconds = $min_execution_time_in_seconds;
    }

    /**
     * @throws \S2lowLegacy\Class\CloudStorageException
     * @throws \S2lowLegacy\Class\WorkerScriptException
     * @throws \S2lowLegacy\Lib\PausingQueueException
     * @throws \S2lowLegacy\Lib\UnrecoverableException
     */
    private function checkAll(): void
    {
        foreach ($this->jobFetchingStrategies->getAllData($this->worker, $this->s2lowLogger) as $data) {
            if ($this->sigTermHandler->isSigtermCalled()) {
                throw new WorkerScriptException('SIGTERM reçu');
            }
            try {
                if ($this->worker->isDataValid($data)) {
                    $this->worker->work($data);
                } else {
                    $this->s2lowLogger->info("Le travail n'est plus à faire, abandon", [$data]);
                }
            } catch (RecoverableException $e) {
                /**  Dans le cas d'une RecoverableException, on continue à traiter les $id
                 *   Sinon, on arrêtera la boucle.
                 **/
                $this->s2lowLogger->debug("RecoverableException " . $e->getMessage());
            }
        }
    }
}
