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
class WorkerRunnerWithSelfUpdatedBeanstalkd implements IWorkerRunnerStrategies
{
    private BeanstalkdWrapper $beanstalkdWrapper;
    private WorkerScript $workerScript;
    private Pheanstalk $queue;

    public function __construct(BeanstalkdWrapper $beanstalkdWrapper, WorkerScript $workerScript, string $queueName)
    {
        $this->beanstalkdWrapper = $beanstalkdWrapper;
        $this->workerScript = $workerScript;
        $this->queue = $this->beanstalkdWrapper->getQueue($queueName);
    }

    public function getAllId(IWorker $worker, S2lowLogger $s2lowLogger): iterable
    {
        while (true) {
            $job = $this->queue->reserve(1);
            if (!$job) {
                return false;
            }
            yield $job->getData();
        }
    }

    public function init(IWorker $worker, S2lowLogger $s2lowLogger): void
    {
        try {
            $this->queue->peekReady($worker->getQueueName());
        } catch (ServerException $th) {
            $s2lowLogger->info("Rebuild queue");
            $this->workerScript->rebuildQueue($worker);
        }
    }
}
