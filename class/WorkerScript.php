<?php

namespace S2lowLegacy\Class;

use Exception;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Lib\PausingQueueException;
use S2lowLegacy\Lib\SigTermHandler;
use S2lowLegacy\Lib\UnrecoverableException;
use Pheanstalk\PheanstalkInterface;

class WorkerScript
{
    private const QUEUE_DELAY_RETRY_IN_SECONDS = 60;
    private const MIN_EXECUTION_TIME_IN_SECONDS = 10; //uniquement pour le mode non beanstalked

    private const NB_MAX_JOBS_TRAITES = 100; // uniquement pour le mode beanstalked

    private $s2lowLogger;
    private $beanstalkdWrapper;
    private $redisMutexWrapper;
    private $sigTermHandlerFactory;

    private $min_execution_time_in_seconds;

    private $objectInstancier;

    /**
     * @var SigTermHandler
     */
    private $sigTermHandler;

    public function __construct(
        BeanstalkdWrapper $beanstalkdWrapper,
        S2lowLogger $s2lowLogger,
        SigTermHandlerFactory $sigTermHandlerFactory,
        ObjectInstancier $objectInstancier,
        RedisMutexWrapper $redisMutexWrapper
    ) {
        $this->s2lowLogger = $s2lowLogger;
        $this->beanstalkdWrapper = $beanstalkdWrapper;
        $this->sigTermHandlerFactory = $sigTermHandlerFactory;
        $this->setMinExecutionTimeInSeconds(self::MIN_EXECUTION_TIME_IN_SECONDS);
        $this->objectInstancier = $objectInstancier;
        $this->redisMutexWrapper = $redisMutexWrapper;
    }


    public function setMinExecutionTimeInSeconds($min_execution_time_in_seconds)
    {
        $this->min_execution_time_in_seconds = $min_execution_time_in_seconds;
    }

    public function putJob(IWorker $IWorker, $data)
    {
        return $this->beanstalkdWrapper->put(
            $IWorker->getQueueName(),
            $data,
            PheanstalkInterface::DEFAULT_DELAY,
            $this->getTTR(get_class($IWorker))  //Some workers, ex. ActesAnalyseFichierAEnvoyerWorker , need more time
        );                                      // to process
    }

    public function putJobByClassName($workerClassName, $data)
    {
        /** @var IWorker $worker */
        $worker = $this->objectInstancier->get($workerClassName);
        return $this->beanstalkdWrapper->put(
            $worker->getQueueName(),
            $data,
            PheanstalkInterface::DEFAULT_DELAY,
            $this->getTTR($workerClassName)  //Some workers, ex. ActesAnalyseFichierAEnvoyerWorker , need more time
        );                                   // to process
    }

    //TODO : Quickfix pour permettre d'utiliser un Worker utilisant des composants Symfony
    // Evite d'avoir à l'instancier
    public function putJobByQueueName($queueName, $data)
    {
        return $this->beanstalkdWrapper->put(
            $queueName,
            $data,
            PheanstalkInterface::DEFAULT_DELAY,
            $this->getTTR($queueName)  //Some workers, ex. ActesAnalyseFichierAEnvoyerWorker , need more time
        );                             // to process
    }

    public function scriptByClassName($workerClassName, $log_enable_stdout = true, $force_old_school_script = false)
    {
        /** @var IWorker $worker */
        $worker = $this->objectInstancier->get($workerClassName);

        return $this->scriptWithLogs($worker, $log_enable_stdout, $force_old_school_script);
    }

    /**
     * @param \S2lowLegacy\Class\IWorker $worker
     * @param mixed $log_enable_stdout
     * @param mixed $force_old_school_script
     * @return bool
     */
    public function scriptWithLogs(
        IWorker $worker,
        bool $log_enable_stdout = true,
        bool $force_old_school_script = false
    ): bool {
        $this->s2lowLogger->setName($worker->getQueueName() . "-script");
        $this->s2lowLogger->enableStdOut($log_enable_stdout);
        return $this->script($worker, $force_old_school_script);
    }

    public function script(IWorker $IWorker, $force_old_school_script = false)
    {
        $this->sigTermHandler = $this->sigTermHandlerFactory->getInstance();
        if (! $force_old_school_script) {
            return $this->beanstalkdWorker($IWorker);
        }
        return $this->oldSchoolScript($IWorker);
    }

    public function rebuildQueue(IWorker $IWorker)
    {
        $this->s2lowLogger->setName($IWorker->getQueueName() . "-rebuild-queue");

        $this->beanstalkdWrapper->emptyQueue($IWorker->getQueueName());
        $this->s2lowLogger->info("Reconstruction de la file " . $IWorker->getQueueName());
        foreach ($IWorker->getAllId() as $id) {
            $this->putJob($IWorker, $id);
            $this->s2lowLogger->info("Ajout en file d'attente", [$id]);
        }
        $this->s2lowLogger->info("Reconstruction de la file " . $IWorker->getQueueName() . ": OK");
    }

    private function beanstalkdWorker(IWorker $IWorker)
    {

        $queue = $this->beanstalkdWrapper->getQueue($IWorker->getQueueName());
        $this->s2lowLogger->info("Démarrage en mode beanstalkd");

        $this->sigTermHandler->setExitOnSignal(true);

        $nbJobsTraités = 0;
        while ($job = $queue->reserve()) {
            $this->sigTermHandler->setExitOnSignal(false);
            $data = "undefined";
            try {
                $data = $job->getData();
                $this->s2lowLogger->info("Travail en cours", [$data]);

                $mutex = $this->redisMutexWrapper->getMutex($IWorker->getMutexName($data));
                $mutex->synchronized(function () use ($IWorker, $data) {
                    $this->syncrhonizedWork($IWorker, $data);
                });
                $queue->delete($job);
                $nbJobsTraités++;
                if ($nbJobsTraités >= self::NB_MAX_JOBS_TRAITES) {
                    $this->s2lowLogger->info("Exit after $nbJobsTraités jobs executed");
                    return true;
                }
            } catch (Exception $e) {
                $this->s2lowLogger->error(
                    $e->getMessage(),
                    [$data,$e->getTraceAsString()]
                );
                if ($e instanceof PausingQueueException) {
                    $seconds = $e->getTimeToWait();
                    $this->s2lowLogger->info("Pausing queue for $seconds seconds");
                    sleep($seconds);
                }
                $queue->release(
                    $job,
                    PheanstalkInterface::DEFAULT_PRIORITY,
                    self::QUEUE_DELAY_RETRY_IN_SECONDS
                );
                continue;
            }
            if ($this->sigTermHandler->isSigtermCalled()) {
                $this->s2lowLogger->info("Exit on signal (after traitement)" . $this->sigTermHandler->getLastSigNo());
                return true;
            }
            $this->sigTermHandler->setExitOnSignal(true);
        }
        return true;
    }

    /**
     * @param IWorker $IWorker
     * @param $data
     * @throws CloudStorageException
     * @throws PausingQueueException
     * @throws RecoverableException
     * @throws UnrecoverableException
     */
    private function syncrhonizedWork(IWorker $IWorker, $data)
    {
        $this->s2lowLogger->debug("Entree section critique");
        if ($IWorker->isDataValid($data)) {
            $IWorker->work($data);
        } else {
            $this->s2lowLogger->info("Le travail n'est plus à faire, abandon", [$data]);
        }
        $this->s2lowLogger->debug("Sortie section critique");
    }

    private function oldSchoolScript(IWorker $IWorker)
    {
        $start = time();

        $this->s2lowLogger->info("Démarrage en mode supervisord");

        try {
            $this->checkAll($IWorker);
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

    /**
     * @param IWorker $IWorker
     * @throws WorkerScriptException
     */
    private function checkAll(IWorker $IWorker)
    {
        $id_list = $IWorker->getAllId();
        $this->s2lowLogger->info(count($id_list) . " travaux trouvées");

        foreach ($id_list as $id) {
            if ($this->sigTermHandler->isSigtermCalled()) {
                throw new WorkerScriptException("SIGTERM reçu");
            }
            $data = $IWorker->getData($id);
            try {
                if ($IWorker->isDataValid($data)) {
                    $IWorker->work($data);
                } else {
                    $this->s2lowLogger->info("Le travail n'est plus à faire, abandon", [$data]);
                }
            } catch (RecoverableException $e) {
                /* Nothing to do*/
            }
        }
    }

    /**
     * @param \S2lowLegacy\Class\IWorker $IWorker
     * @return int|string
     */
    private function getTTR(string $IWorkerClassName): string|int
    {
        try {
            $delay = $IWorkerClassName::PHEANSTALK_TTR;
        } catch (Exception $exception) {
            $delay = PheanstalkInterface::DEFAULT_TTR;
        } catch (\Error $error) {
            $delay = PheanstalkInterface::DEFAULT_TTR;
        }
        return $delay;
    }
}
