<?php

use malkusch\lock\mutex\PHPRedisMutex;
use Pheanstalk\Job;
use Pheanstalk\Pheanstalk;
use S2lowLegacy\Class\BeanstalkdWrapper;
use S2lowLegacy\Class\IWorker;
use S2lowLegacy\Class\RedisMutexWrapper;
use S2lowLegacy\Class\SigTermHandlerFactory;
use S2lowLegacy\Class\WorkerRunnerBuilder;
use S2lowLegacy\Class\WorkerRunnerWithDataFromDB;
use S2lowLegacy\Class\WorkerScript;
use S2lowLegacy\Lib\SigTermHandler;

class WorkerRunnerBuilderTest extends S2lowTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $beanstalkdWrapper = $this->getMockBuilder(BeanstalkdWrapper::class)->disableOriginalConstructor()->getMock();
        $beanstalkdWrapper->method('put')->willReturn(true);
        $this->getObjectInstancier()->set(BeanstalkdWrapper::class, $beanstalkdWrapper);

        $this->queue = $this->getMockBuilder(Pheanstalk::class)
            ->disableOriginalConstructor()
            ->getMock();

        $beanstalkdWrapper = $this->getMockBuilder(BeanstalkdWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $redisWrapper = $this->getMockBuilder(RedisMutexWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mutexMock = $this->getMockBuilder(PHPRedisMutex::class)
            ->disableOriginalConstructor()
            ->getMock();

        $redisWrapper->method('getMutex')->willReturn($mutexMock);

        $beanstalkdWrapper->method('getQueue')->willReturn($this->queue);

        $this->getObjectInstancier()->set(BeanstalkdWrapper::class, $beanstalkdWrapper);
        $this->getObjectInstancier()->set(RedisMutexWrapper::class, $redisWrapper);
    }

    private function setUpWorkerBuilder($job, $numberOfJobs = 1)
    {

        $jobQueue = array_fill(0, $numberOfJobs, $job);
        $jobQueue[] = false;

        $this->queue->method('reserve')->will($this->onConsecutiveCalls(...$jobQueue));

        /** @var IWorker | \PHPUnit\Framework\MockObject\MockObject $IWorker */
        $IWorker = $this->getMockForAbstractClass(IWorker::class);
        $IWorker->method('getData')->willReturn([1]);

        /** @var WorkerRunnerBuilder $workerBuilder */
        $workerBuilder = $this->getObjectInstancier()->get(WorkerRunnerBuilder::class);
        return $workerBuilder->script($IWorker)->work();
    }
    public function testBeanstalked()
    {
        $job = $this->getMockBuilder(Job::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertTrue($this->setUpWorkerBuilder($job));
        $logs_records = $this->getLogRecords();
        $this->assertEquals("Travail en cours", $logs_records[1]['message']);
    }

    public function testBeanstalkedisLimited()
    {
        $job = $this->getMockBuilder(Job::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertTrue($this->setUpWorkerBuilder($job, 200));
        $logs_records = $this->getLogRecords();
        $this->assertEquals("Exit after 100 jobs executed", $logs_records[101]['message']);
        $this->assertArrayNotHasKey(102, $logs_records);
    }

    public function testBeanstalkedFailed()
    {
        $job = $this->getMockBuilder(Job::class)
            ->disableOriginalConstructor()
            ->getMock();
        $job
            ->method('getData')
            ->willThrowException(new Exception("foo"));

        $this->assertTrue($this->setUpWorkerBuilder($job));
        $logs_records = $this->getLogRecords();
        $this->assertEquals("foo", $logs_records[1]['message']);
    }

    public function testScript()
    {
        $IWorker = $this->getMockForAbstractClass(IWorker::class);
        $IWorker->method("getAllId")->willReturn([1]);
        /** @var IWorker $IWorker */
        $workerBuilder = $this->getObjectInstancier()->get(WorkerRunnerBuilder::class);

        $this->assertTrue(
            $workerBuilder->scriptWithLogs($IWorker, false, WorkerRunnerWithDataFromDB::class)->work()
        );
    }

    public function testScriptTerm()
    {
        $sigTermHandler = $this->getMockBuilder(SigTermHandler::class)->disableOriginalConstructor()->getMock();
        $sigTermHandler->method('isSigtermCalled')->willReturn(true);

        $sigTermHandlerFactory = $this->getMockBuilder(SigTermHandlerFactory::class)->getMock();
        $sigTermHandlerFactory->method('getInstance')->willReturn($sigTermHandler);
        $this->getObjectInstancier()->set(SigTermHandlerFactory::class, $sigTermHandlerFactory);

        $IWorker = $this->getMockForAbstractClass(IWorker::class);
        $IWorker->method("getAllId")->willReturn([1]);
        /** @var IWorker $IWorker */

        $workerRunnerBuilder = $this->getObjectInstancier()->get(WorkerRunnerBuilder::class);
        $this->assertTrue($workerRunnerBuilder->script($IWorker, WorkerRunnerWithDataFromDB::class)->work());
        $logs_records = $this->getLogRecords();
        $this->assertEquals("SIGTERM reçu", $logs_records[2]['message']);
    }

    public function testScriptFailed()
    {
        $IWorker = $this->getMockForAbstractClass(IWorker::class);
        $IWorker
            ->method("getAllId")
            ->willThrowException(new Exception("foo"));
        /** @var IWorker $IWorker */

        $workerRunnerBuilder = $this->getObjectInstancier()->get(WorkerRunnerBuilder::class);
        $this->assertFalse($workerRunnerBuilder->script($IWorker, WorkerRunnerWithDataFromDB::class)->work());
        $logs_records = $this->getLogRecords();
        $this->assertEquals("Erreur lors de l'execution du script : foo", $logs_records[1]['message']);
    }
}
