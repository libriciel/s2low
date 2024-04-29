<?php

declare(strict_types=1);

namespace S2lowLegacy\Class;

use Pheanstalk\Exception\ServerException;
use Pheanstalk\Pheanstalk;

/**
 * WorkerRunner qui va
 * 1/ traiter sa propre file beanstalkd
 * 2/ la remplir automatiquement lorsque celle-ci est vide
 * ATTENTION : il ne faut pas reserve les jobs qui échouent, sinon la file ne se videra pas,
 * et restera bloquée sur les jobs qui failent.
 */
class JobFetcherFromSelfUpdatedBeanstalkd implements JobFetchingStrategies
{
    private WorkerScript $workerScript;
    private Pheanstalk $queue;

    public function __construct(BeanstalkdWrapper $beanstalkdWrapper, WorkerScript $workerScript, string $queueName)
    {
        $this->workerScript = $workerScript;
        $this->queue = $beanstalkdWrapper->getQueue($queueName);
    }

    public function getAllData(IWorker $worker, S2lowLogger $s2lowLogger): iterable
    {
        while (true) {
            $job = $this->queue->reserve(0);
            if (!$job) {
                return false;
            }
            $data = $job->getData();
            $this->queue->delete($job);
            yield $data;
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
