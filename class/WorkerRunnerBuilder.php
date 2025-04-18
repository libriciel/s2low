<?php

declare(strict_types=1);

namespace S2lowLegacy\Class;

use RuntimeException;

class WorkerRunnerBuilder
{
    private const MIN_EXECUTION_TIME_IN_SECONDS = 10; //uniquement pour le mode non beanstalked

    private S2lowLogger $s2lowLogger;
    private BeanstalkdWrapper $beanstalkdWrapper;
    private RedisMutexWrapper $redisMutexWrapper;
    private SigTermHandlerFactory $sigTermHandlerFactory;
    private WorkerScript $workerScript;
    private int $totalTryBeforeWorkerDie;
    private int $secondsBetweenEachTry;

    public function __construct(
        BeanstalkdWrapper $beanstalkdWrapper,
        S2lowLogger $s2lowLogger,
        SigTermHandlerFactory $sigTermHandlerFactory,
        RedisMutexWrapper $redisMutexWrapper,
        WorkerScript $workerScript,
        int $totalTryBeforeWorkerDie,
        int $secondsBetweenEachTry
    ) {
        $this->s2lowLogger = $s2lowLogger;
        $this->beanstalkdWrapper = $beanstalkdWrapper;
        $this->sigTermHandlerFactory = $sigTermHandlerFactory;
        $this->redisMutexWrapper = $redisMutexWrapper;
        $this->workerScript = $workerScript;
        $this->totalTryBeforeWorkerDie = $totalTryBeforeWorkerDie;
        $this->secondsBetweenEachTry = $secondsBetweenEachTry;
    }

    public function scriptWithLogs(
        IWorker $worker,
        bool $log_enable_stdout = true,
        string $scriptType = WorkerRunnerWithDataFromBeanstalkd::class
    ): WorkerRunner {
        $this->s2lowLogger->setName($worker->getQueueName() . '-script');
        $this->s2lowLogger->enableStdOut($log_enable_stdout);
        return $this->script($worker, $scriptType);
    }

    public function script(
        IWorker $IWorker,
        string $scriptType = WorkerRunnerWithDataFromBeanstalkd::class
    ): WorkerRunner {
        return match ($scriptType) {
            WorkerRunnerWithDataFromBeanstalkd::class => new WorkerRunnerWithDataFromBeanstalkd(
                $IWorker,
                $this->beanstalkdWrapper,
                $this->s2lowLogger,
                $this->sigTermHandlerFactory->getInstance(),
                $this->redisMutexWrapper,
                $this->totalTryBeforeWorkerDie,
                $this->secondsBetweenEachTry
            ),
            JobFetcherFromDB::class => new CustomizableWorkerRunner(
                $IWorker,
                $this->s2lowLogger,
                $this->sigTermHandlerFactory->getInstance(),
                self::MIN_EXECUTION_TIME_IN_SECONDS,
                new JobFetcherFromDB()
            ),
            JobFetcherFromSelfUpdatedBeanstalkd::class => new CustomizableWorkerRunner(
                $IWorker,
                $this->s2lowLogger,
                $this->sigTermHandlerFactory->getInstance(),
                self::MIN_EXECUTION_TIME_IN_SECONDS,
                new JobFetcherFromSelfUpdatedBeanstalkd(
                    $this->beanstalkdWrapper,
                    $this->workerScript,
                    $IWorker->getQueueName()
                )
            ),
            default => throw new RuntimeException("Unknown Worker Type : $scriptType"),
        };
    }
}
