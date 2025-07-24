<?php

namespace PHPUnit\class\actes;

use S2lowLegacy\Class\actes\ActesAntivirusWorker;
use S2lowLegacy\Class\actes\ActesEnvoiFichierWorker;
use S2lowLegacy\Class\IWorker;
use S2lowLegacy\Class\WorkersDelayRetry;
use S2lowTestCase;

class WorkersDelayRetryTest extends S2lowTestCase
{
    /**
     * @dataProvider delays
     */
    public function testWorkersDelayRetry(IWorker $worker, int $delay): void
    {
            self::assertSame(
                $delay,
                WorkersDelayRetry::get($worker)
            );
    }

    public function delays()
    {
            $workerWithDefaultDelay = $this->getObjectInstancier()->get(ActesAntivirusWorker::class);
            $actesEnvoiFichierWorker = $this->getObjectInstancier()->get(ActesEnvoiFichierWorker::class);

        return [
            [$workerWithDefaultDelay, WorkersDelayRetry::DEFAULT_DELAY_RETRY_IN_SECONDS],
            [$actesEnvoiFichierWorker,300]
        ];
    }
}
