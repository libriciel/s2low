<?php

namespace S2lowLegacy\Class;

use Pheanstalk\PheanstalkInterface;
use Psr\Log\LoggerInterface;
use S2lowLegacy\Lib\PausingQueueException;
use S2lowLegacy\Lib\SigTermHandler;
use S2lowLegacy\Lib\UnrecoverableException;
use Throwable;

class WorkerRunnerWithDataFromBeanstalkd implements WorkerRunner
{
    private const NB_MAX_JOBS_TRAITES = 100; // uniquement pour le mode beanstalked
    /**
     * @var \S2lowLegacy\Class\IWorker
     */
    private IWorker $worker;
    /**
     * @var \S2lowLegacy\Class\BeanstalkdWrapper
     */
    private BeanstalkdWrapper $beanstalkdWrapper;
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $s2lowLogger;
    /**
     * @var \S2lowLegacy\Lib\SigTermHandler
     */
    private SigTermHandler $sigTermHandler;
    /**
     * @var \S2lowLegacy\Class\RedisMutexWrapper
     */
    private RedisMutexWrapper $redisMutexWrapper;

    public function __construct(
        IWorker $worker,
        BeanstalkdWrapper $beanstalkdWrapper,
        LoggerInterface $s2lowLogger,
        SigTermHandler $sigTermHandler,
        RedisMutexWrapper $redisMutexWrapper
    ) {
        $this->worker = $worker;
        $this->beanstalkdWrapper = $beanstalkdWrapper;
        $this->s2lowLogger = $s2lowLogger;
        $this->sigTermHandler = $sigTermHandler;
        $this->redisMutexWrapper = $redisMutexWrapper;
    }

    /**
     * @param IWorker $IWorker
     * @param $data
     * @throws CloudStorageException
     * @throws PausingQueueException
     * @throws RecoverableException
     * @throws UnrecoverableException
     */
    private function syncrhonizedWork(IWorker $IWorker, $data): void
    {
        $this->s2lowLogger->debug('Entree section critique');
        if ($IWorker->isDataValid($data)) {
            $IWorker->work($data);
        } else {
            $this->s2lowLogger->info("Le travail n'est plus à faire, abandon", [$data]);
        }
        $this->s2lowLogger->debug('Sortie section critique');
    }
    public function work(): bool
    {

        $queue = $this->beanstalkdWrapper->getQueue($this->worker->getQueueName());
        $this->s2lowLogger->info("Démarrage de {$this->worker->getQueueName()} en mode beanstalkd");

        $this->sigTermHandler->setExitOnSignal(true);

        $nbJobsTraités = 0;
        $nbIterationsAVide = 0;                          // On va relancer périodiquement le worker
        while ($nbIterationsAVide < 10) {                // Si aucun job, toutes les 10 iterations
            $job = $queue->reserve(30);           // On attend au max 30s un job dispo
                                                         // Ce qui fait une boucle à vide de 5 minutes
            if (!$job) {                                 // Et on logge si aucun job disponible.
                $nbIterationsAVide++;
                $this->s2lowLogger->info('Aucun job à traiter.');
                continue;
            }

            $this->sigTermHandler->setExitOnSignal(false);
            $data = 'undefined';
            try {
                $data = $job->getData();
                $stats = $queue->statsJob($job);
                $ttr = $stats['ttr'] ?? null;
                $age = $stats['age'] ?? null;
                $this->s2lowLogger->info('Travail en cours', ['data' => $data, 'ttr' => $ttr,'age' => $age]);

                $mutex = $this->redisMutexWrapper->getMutex($this->worker->getMutexName($data));
                $workerToUse = $this->worker;
                $mutex->synchronized(function () use ($workerToUse, $data) {
                    $this->syncrhonizedWork($workerToUse, $data);
                });
                $queue->delete($job);
                $nbJobsTraités++;
                if ($nbJobsTraités >= self::NB_MAX_JOBS_TRAITES) {
                    $this->s2lowLogger->info("Exit after $nbJobsTraités jobs executed");
                    return true;
                }
            } catch (Throwable $e) {
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
                    WorkersQueueProperties::getPriority($this->worker),
                    WorkersQueueProperties::getDelay($this->worker)
                );
                continue;
            }
            if ($this->sigTermHandler->isSigtermCalled()) {
                $this->s2lowLogger->info('Exit on signal (after traitement)' . $this->sigTermHandler->getLastSigNo());
                break;
            }
            $this->sigTermHandler->setExitOnSignal(true);
        }
        return true;
    }
}
