<?php

namespace S2lowLegacy\Class;

use Pheanstalk\PheanstalkInterface;

class WorkerScript
{
    public function __construct(
        private readonly BeanstalkdWrapper $beanstalkdWrapper,
        private readonly S2lowLogger $s2lowLogger
    ) {
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

    public function putJobByQueueName($queueName, $data)
    {
        return $this->beanstalkdWrapper->put(
            $queueName,
            $data,
            PheanstalkInterface::DEFAULT_DELAY,
            $this->getTTR($queueName)  //Some workers, ex. ActesAnalyseFichierAEnvoyerWorker , need more time
        );                             // to process
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


    private function getTTR(string $IWorkerClassName): string|int
    {
        try {
            $delay = $IWorkerClassName::PHEANSTALK_TTR;
        } catch (\Throwable $exception) {
            $delay = PheanstalkInterface::DEFAULT_TTR;
        }
        return $delay;
    }
}
