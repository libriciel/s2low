<?php

declare(strict_types=1);

namespace PHPUnit\class;

use PHPUnit\S2lowTestCase;
use S2lowLegacy\Class\BeanstalkdWrapper;
use S2lowLegacy\Class\IWorker;
use S2lowLegacy\Class\WorkerScript;

class WorkerScriptTest extends S2lowTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $beanstalkdWrapper = $this->getMockBuilder(BeanstalkdWrapper::class)->disableOriginalConstructor()->getMock();
        $beanstalkdWrapper->method('put')->willReturn(true);
        $this->getObjectInstancier()->set(BeanstalkdWrapper::class, $beanstalkdWrapper);
    }

    public function testPutJob()
    {
        /** @var IWorker $IWorker */
        $IWorker = $this->getMockForAbstractClass(IWorker::class);
        $this->getObjectInstancier()->set('MockWorker', $IWorker);

        $workerScript = $this->getObjectInstancier()->get(WorkerScript::class);
        $this->assertTrue($workerScript->putJobByClassName("MockWorker", true));
    }

    public function testRebuildQueue()
    {
        $IWorker = $this->getMockForAbstractClass(IWorker::class);
        $IWorker->method("getAllId")->willReturn([1]);
        /** @var IWorker $IWorker */

        $workerScript = $this->getObjectInstancier()->get(WorkerScript::class);
        $workerScript->rebuildQueue($IWorker);
        $logs_records = $this->getLogRecords();
        $this->assertEquals("Ajout en file d'attente", $logs_records[1]['message']);
    }
}
