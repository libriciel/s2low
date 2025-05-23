<?php

namespace S2low\Command;

use S2low\Services\Helios\HeliosEnvoiWorkerFactory;
use S2lowLegacy\Class\IWorker;
use S2lowLegacy\Class\WorkerScript;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'beanstalkd:rebuild-queue',
    description: 'Rebuilds beanstalk queue for every worker tagged queue_rebuilding_worker.',
)]
class RebuildQueueCommand extends Command
{
    private iterable $workers;

    public function __construct(
        iterable $workers,
        private readonly HeliosEnvoiWorkerFactory $heliosEnvoiWorkerFactory,
        private readonly WorkerScript $workerScript
    ) {
        $this->workers = $workers;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->workers as $worker) {
            $this->rebuildQueue($worker);
        }

        $this->rebuildQueue($this->heliosEnvoiWorkerFactory->get(true));
        $this->rebuildQueue($this->heliosEnvoiWorkerFactory->get(false));

        return Command::SUCCESS;
    }

    private function rebuildQueue(IWorker $worker): void
    {
        $this->workerScript->rebuildQueue($worker);
    }
}
