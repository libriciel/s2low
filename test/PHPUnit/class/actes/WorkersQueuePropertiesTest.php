<?php

namespace PHPUnit\class\actes;

use Pheanstalk\PheanstalkInterface;
use S2lowLegacy\Class\actes\ActesAntivirusWorker;
use S2lowLegacy\Class\actes\ActesEnvoiFichierWorker;
use S2lowLegacy\Class\IWorker;
use S2lowLegacy\Class\WorkersQueueProperties;
use S2lowTestCase;

class WorkersQueuePropertiesTest extends S2lowTestCase
{
    /**
     * @dataProvider delays
     */
    public function testWorkersDelayRetry(IWorker $worker, int $delay): void
    {
            self::assertSame(
                $delay,
                WorkersQueueProperties::getDelay($worker)
            );
    }

    public function delays()
    {
            $workerWithDefaultDelay = $this->getObjectInstancier()->get(ActesAntivirusWorker::class);
            $actesEnvoiFichierWorker = $this->getObjectInstancier()->get(ActesEnvoiFichierWorker::class);

        return [
            [$workerWithDefaultDelay, WorkersQueueProperties::DEFAULT_DELAY_RETRY_IN_SECONDS],
            [$actesEnvoiFichierWorker,300]
        ];
    }
    /**
     * @dataProvider priorities
     */
    public function testPriority(IWorker $worker, int $priority): void
    {
        self::assertSame(
            $priority,
            WorkersQueueProperties::getPriority($worker)
        );
    }

    public function priorities()
    {
        $workerWithDefaultPriority = $this->getObjectInstancier()->get(ActesAntivirusWorker::class);
        $actesEnvoiFichierWorker = $this->getObjectInstancier()->get(ActesEnvoiFichierWorker::class);

        return [
            [$workerWithDefaultPriority, PheanstalkInterface::DEFAULT_PRIORITY],
            [$actesEnvoiFichierWorker,512]
        ];
    }
}
