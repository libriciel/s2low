<?php

namespace S2lowLegacy\Class;

class WorkerRunnerBuilder
{
    private const MIN_EXECUTION_TIME_IN_SECONDS = 10; //uniquement pour le mode non beanstalked

    private S2lowLogger $s2lowLogger;
    private BeanstalkdWrapper $beanstalkdWrapper;
    private RedisMutexWrapper $redisMutexWrapper;
    private SigTermHandlerFactory $sigTermHandlerFactory;
    private WorkerScript $workerScript;


    /**
     * @param \S2lowLegacy\Class\BeanstalkdWrapper $beanstalkdWrapper
     * @param \S2lowLegacy\Class\S2lowLogger $s2lowLogger
     * @param \S2lowLegacy\Class\SigTermHandlerFactory $sigTermHandlerFactory
     * @param \S2lowLegacy\Class\RedisMutexWrapper $redisMutexWrapper
     */
    public function __construct(
        BeanstalkdWrapper $beanstalkdWrapper,
        S2lowLogger $s2lowLogger,
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

    /**
     * @param \S2lowLegacy\Class\IWorker $worker
     * @param mixed $log_enable_stdout
     * @param mixed $force_old_school_script
     * @return \S2lowLegacy\Class\WorkerRunnerWithDataFromDB|WorkerRunnerWithDataFromBeanstalkd
     */
    public function scriptWithLogs(
        IWorker $worker,
        bool $log_enable_stdout = true,
        string $scriptType = WorkerRunnerWithDataFromBeanstalkd::class
    ): WorkerRunnerWithDataFromDB|WorkerRunnerWithDataFromBeanstalkd|WorkerRunnerWithSelfBeanstalkd {
        $this->s2lowLogger->setName($worker->getQueueName() . "-script");
        $this->s2lowLogger->enableStdOut($log_enable_stdout);
        return $this->script($worker, $scriptType);
    }

    public function script(IWorker $IWorker, string $scriptType = WorkerRunnerWithDataFromBeanstalkd::class)
    {
        switch ($scriptType) {
            case WorkerRunnerWithDataFromBeanstalkd::class:
                return  new WorkerRunnerWithDataFromBeanstalkd(
                    $IWorker,
                    $this->beanstalkdWrapper,
                    $this->s2lowLogger,
                    $this->sigTermHandlerFactory->getInstance(),
                    $this->redisMutexWrapper
                );
            case WorkerRunnerWithDataFromDB::class:
                return new WorkerRunnerWithDataFromDB(
                    $IWorker,
                    $this->s2lowLogger,
                    $this->sigTermHandlerFactory->getInstance(),
                    self::MIN_EXECUTION_TIME_IN_SECONDS
                );
            case WorkerRunnerWithSelfBeanstalkd::class:
                return new WorkerRunnerWithSelfBeanstalkd(
                    $IWorker,
                    $this->beanstalkdWrapper,
                    $this->s2lowLogger,
                    $this->sigTermHandlerFactory->getInstance(),
                    $this->redisMutexWrapper,
                    $this->workerScript,
                    self::MIN_EXECUTION_TIME_IN_SECONDS
                );
            default:
                throw new \RuntimeException("Unknown Worker Type : $scriptType");
        }
    }
}
