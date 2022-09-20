<?php

namespace S2low\Command;

use S2lowLegacy\Class\S2lowLogger;
use S2lowLegacy\Class\WorkerScript;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RebuildQueueCommand extends Command
{
    private iterable $workers;
    /**
     * @var \S2lowLegacy\Class\S2lowLogger
     */
    private S2lowLogger $s2lowLogger;
    /**
     * @var \S2lowLegacy\Class\WorkerScript
     */
    private WorkerScript $workerScript;

    public function __construct(iterable $workers, S2lowLogger $s2lowLogger, WorkerScript $workerScript)
    {
        $this->workers = $workers;
        $this->s2lowLogger = $s2lowLogger;
        $this->s2lowLogger->enableStdOut();
        $this->workerScript = $workerScript;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('beanstalkd:rebuild-queue')
            ->setDescription(
                "Rebuilds beanstalk queue for every worker tagged queue_rebuilding_worker"
            );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->workers as $worker) {
            $this->s2lowLogger->setName($worker->getQueueName() . "-rebuild-queue");
            $this->workerScript->rebuildQueue($worker);
        }
        return 0;
    }
}
