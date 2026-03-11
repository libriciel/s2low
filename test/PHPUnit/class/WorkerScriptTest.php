<?php

use Monolog\Level;
use Pheanstalk\PheanstalkInterface;
use PHPUnit\Framework\MockObject\MockObject;
use S2lowLegacy\Class\actes\ActesAnalyseFichierAEnvoyerWorker;
use S2lowLegacy\Class\BeanstalkdWrapper;
use S2lowLegacy\Class\IWorker;
use S2lowLegacy\Class\WorkerScript;
use S2lowLegacy\Lib\ObjectInstancier;

class WorkerScriptTest extends S2lowTestCase
{
    private BeanstalkdWrapper|MockObject $beanstalkdWrapper;

    public function setUp(): void
    {
        parent::setUp();
        $this->beanstalkdWrapper = $this->getMockBuilder(BeanstalkdWrapper::class)->disableOriginalConstructor()->getMock();
        $this->beanstalkdWrapper->method('put')->willReturn(true);
        $this->getObjectInstancier()->set(BeanstalkdWrapper::class, $this->beanstalkdWrapper);
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

        $workerScript = $this->createWorkerScript();
        $workerScript->rebuildQueue($IWorker);
        self::assertTrue(
            $this->testHandler->hasRecord(
                'Ajout en file d\'attente',
                Level::Info
            )
        );
    }

    private function createWorkerScript()
    {
        return new WorkerScript(
            self::getContainer()->get(BeanstalkdWrapper::class),
            $this->logger,
            self::getContainer()->get(ObjectInstancier::class),
        );
    }

    public function testPutJobByQueueName()
    {
        /** @var WorkerScript $workerScript */
        $workerScript = $this->getObjectInstancier()->get(WorkerScript::class);
        $this->beanstalkdWrapper->expects(self::once())
            ->method('put')
            ->with(
                'GenericQueueName',
                67,
                PheanstalkInterface::DEFAULT_DELAY,
            )
            ->willReturn(true);

        $workerScript->putJobByQueuename('GenericQueueName', 67);
    }
    public function testPutJobByQueueNameWithTTR()
    {
        /** @var WorkerScript $workerScript */
        $workerScript = $this->getObjectInstancier()->get(WorkerScript::class);
        $this->beanstalkdWrapper->expects(self::once())
            ->method('put')
            ->with(
                'GenericQueueName',
                67,
                PheanstalkInterface::DEFAULT_DELAY,
                ActesAnalyseFichierAEnvoyerWorker::PHEANSTALK_TTR
            )
            ->willReturn(true);

        $workerScript->putJobByQueuename(
            'GenericQueueName',
            67,
            ActesAnalyseFichierAEnvoyerWorker::PHEANSTALK_TTR
        );
    }
}
