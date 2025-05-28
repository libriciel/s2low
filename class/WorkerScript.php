<?php

namespace S2lowLegacy\Class;

use S2lowLegacy\Lib\ObjectInstancier;
use Pheanstalk\PheanstalkInterface;

class WorkerScript
{
    private const MIN_EXECUTION_TIME_IN_SECONDS = 10; //uniquement pour le mode non beanstalked


    public function __construct(
        private readonly BeanstalkdWrapper $beanstalkdWrapper,
        private S2lowLogger $s2lowLogger,
        private readonly ObjectInstancier $objectInstancier,
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
    public function putJobByQueueName($queueName, $data, $ttr = PheanstalkInterface::DEFAULT_TTR)
    {
        return $this->beanstalkdWrapper->put(
            $queueName,
            $data,
            PheanstalkInterface::DEFAULT_DELAY,
            $ttr  //Some workers, ex. ActesAnalyseFichierAEnvoyerWorker , need more time
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

    public function setLogger(S2lowLogger $s2lowLogger)
    {
        $this->s2lowLogger = $s2lowLogger;
    }
}
