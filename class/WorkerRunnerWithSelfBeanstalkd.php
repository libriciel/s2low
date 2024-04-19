<?php

declare(strict_types=1);

namespace S2lowLegacy\Class;

use Pheanstalk\Exception\CommandException;
use Pheanstalk\Exception\ServerException;
use Pheanstalk\Job;
use Pheanstalk\Pheanstalk;
use Pheanstalk\PheanstalkInterface;
use S2lowLegacy\Lib\SigTermHandler;
use Throwable;

/**
 * WorkerRunner qui va
 * 1/ traiter sa propre file beanstalkd
 * 2/ la remplir automatiquement lorsque celle-ci est vide
 * ATTENTION : il ne faut pas reserve les jobs qui échouent, sinon la file ne se videra pas,
 * et restera bloquée sur les jobs qui failent.
 */
class WorkerRunnerWithSelfBeanstalkd extends AbstractWorkerRunner
{
    private BeanstalkdWrapper $beanstalkdWrapper;
    private WorkerScript $workerScript;
    private Job|false $job = false;
    private Pheanstalk $queue;

    public function __construct(
        IWorker $worker,
        BeanstalkdWrapper $beanstalkdWrapper,
        S2lowLogger $s2lowLogger,
        SigTermHandler $sigTermHandler,
        RedisMutexWrapper $redisMutexWrapper,
        WorkerScript $workerScript,
        int $min_execution_time_in_seconds
    ) {
        $this->beanstalkdWrapper = $beanstalkdWrapper;
        $this->workerScript = $workerScript;
        parent::__construct($worker, $s2lowLogger, $sigTermHandler, $min_execution_time_in_seconds);
        $this->queue = $this->beanstalkdWrapper->getQueue($this->worker->getQueueName());
    }

    protected function getAllId(): iterable
    {
        while (true) {
            $this->job = $this->queue->reserve(1);
            if (!$this->job) {
                return false;
            }
            yield $this->job->getData();
        }
    }

    protected function init(): void
    {
        try {
            $this->queue->peekReady($this->worker->getQueueName());
        } catch (ServerException $th) {
            $this->s2lowLogger->info("Rebuild queue");
            $this->workerScript->rebuildQueue($this->worker);
        }
    }
}
