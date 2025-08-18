<?php

namespace S2low\Command;

use LogicException;
use Psr\Log\LoggerInterface;
use S2low\Services\Helios\HeliosEnvoiWorkerFactory;
use S2lowLegacy\Class\WorkerScript;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Rebuilds beanstalk queue for every worker tagged queue_rebuilding_worker
 */
class RebuildQueueCommand extends Command
{
    private iterable $workers;
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $s2lowLogger;
    /**
     * @var WorkerScript
     */
    private WorkerScript $workerScript;
    /**
     * @var HeliosEnvoiWorkerFactory
     */
    private HeliosEnvoiWorkerFactory $heliosEnvoiWorkerFactory;

    /**
     * @param iterable $workers
     * @param \S2low\Services\Helios\HeliosEnvoiWorkerFactory $heliosEnvoiWorkerFactory
     * @param LoggerInterface $s2lowLogger
     * @param \S2lowLegacy\Class\WorkerScript $workerScript
     */
    public function __construct(
        iterable $workers,
        HeliosEnvoiWorkerFactory $heliosEnvoiWorkerFactory,
        LoggerInterface $s2lowLogger,
        WorkerScript $workerScript
    ) {
        $this->workers = $workers;
        $this->heliosEnvoiWorkerFactory = $heliosEnvoiWorkerFactory;
        $this->s2lowLogger = $s2lowLogger;
        $this->workerScript = $workerScript;
        parent::__construct();
    }

    /**
     * Configures the current command.
     */
    protected function configure(): void
    {
        $this
            ->setName('beanstalkd:rebuild-queue')
            ->setDescription(
                "Rebuilds beanstalk queue for every worker tagged queue_rebuilding_worker"
            );
    }

    /**
     * Executes the current command.
     *
     * This method is not abstract because you can use this class
     * as a concrete class. In this case, instead of defining the
     * execute() method, you set the code to execute by passing
     * a Closure to the setCode() method.
     *
     * @return int 0 if everything went fine, or an exit code
     *
     * @throws LogicException When this abstract method is not implemented
     *
     * @see setCode()
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->workers as $worker) {
            $this->rebuildQueue($worker);
        }

        $this->rebuildQueue($this->heliosEnvoiWorkerFactory->get(true));
        $this->rebuildQueue($this->heliosEnvoiWorkerFactory->get(false));

        return 0;
    }

    /**
     * @param mixed $worker
     * @return void
     */
    private function rebuildQueue(mixed $worker): void
    {
        $this->workerScript->rebuildQueue($worker);
    }
}
