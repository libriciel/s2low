<?php

declare(strict_types=1);

namespace S2lowLegacy\Class;

use Psr\Log\LoggerInterface;
use RuntimeException;

class WorkerRunnerBuilder
{
    private const MIN_EXECUTION_TIME_IN_SECONDS = 10; //uniquement pour le mode non beanstalked

    private LoggerInterface $s2lowLogger;
    private BeanstalkdWrapper $beanstalkdWrapper;
    private RedisMutexWrapper $redisMutexWrapper;
    private SigTermHandlerFactory $sigTermHandlerFactory;
    private WorkerScript $workerScript;

    public function __construct(
        BeanstalkdWrapper $beanstalkdWrapper,
        LoggerInterface $s2lowLogger,
        SigTermHandlerFactory $sigTermHandlerFactory,
        RedisMutexWrapper $redisMutexWrapper,
        WorkerScript $workerScript
    ) {
        $this->s2lowLogger = $s2lowLogger;
        $this->beanstalkdWrapper = $beanstalkdWrapper;
        $this->sigTermHandlerFactory = $sigTermHandlerFactory;
        $this->redisMutexWrapper = $redisMutexWrapper;
        $this->workerScript = $workerScript;
    }

    public function scriptWithLogs(
        IWorker $worker,
        bool $log_enable_stdout = true,
        string $scriptType = WorkerRunnerWithDataFromBeanstalkd::class
    ): WorkerRunner {
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
                $this->redisMutexWrapper
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
