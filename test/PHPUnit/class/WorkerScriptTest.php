<?php

use malkusch\lock\mutex\PHPRedisMutex;
use S2lowLegacy\Class\BeanstalkdWrapper;
use S2lowLegacy\Class\IWorker;
use S2lowLegacy\Class\RedisMutexWrapper;
use S2lowLegacy\Class\SigTermHandlerFactory;
use S2lowLegacy\Class\WorkerScript;
use S2lowLegacy\Lib\SigTermHandler;

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

    public function testScript()
    {
        $IWorker = $this->getMockForAbstractClass(IWorker::class);
        $IWorker->method("getAllId")->willReturn([1]);
        /** @var IWorker $IWorker */
        $workerScript = $this->getObjectInstancier()->get(WorkerScript::class);
        $this->getObjectInstancier()->set('MockWorker', $IWorker);
        $this->assertTrue($workerScript->scriptByClassName('MockWorker', false, true));
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

        $workerScript = $this->getObjectInstancier()->get(WorkerScript::class);
        $this->assertTrue($workerScript->script($IWorker, true));
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

        $workerScript = $this->getObjectInstancier()->get(WorkerScript::class);
        $this->assertFalse($workerScript->script($IWorker, true));
        $logs_records = $this->getLogRecords();
        $this->assertEquals("Erreur lors de l'execution du script : foo", $logs_records[1]['message']);
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

    public function testBeanstalked()
    {
        $job = $this->getMockBuilder(Pheanstalk\Job::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertTrue($this->runBeanstalkd($job));
        $logs_records = $this->getLogRecords();
        $this->assertEquals("Travail en cours", $logs_records[1]['message']);
    }

    public function testBeanstalkedisLimited()
    {
        $job = $this->getMockBuilder(Pheanstalk\Job::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertTrue($this->runBeanstalkd($job, 200));
        $logs_records = $this->getLogRecords();
        $this->assertEquals("Exit after 100 jobs executed", $logs_records[101]['message']);
        $this->assertArrayNotHasKey(102, $logs_records);
    }

    public function testBeanstalkedFailed()
    {
        $job = $this->getMockBuilder(Pheanstalk\Job::class)
            ->disableOriginalConstructor()
            ->getMock();
        $job
            ->method('getData')
            ->willThrowException(new Exception("foo"));

        $this->assertTrue($this->runBeanstalkd($job));
        $logs_records = $this->getLogRecords();
        $this->assertEquals("foo", $logs_records[1]['message']);
    }

    private function runBeanstalkd($job, $numberOfJobs = 1)
    {
        $queue = $this->getMockBuilder(\Pheanstalk\Pheanstalk::class)
            ->disableOriginalConstructor()
            ->getMock();

        $jobQueue = array_fill(0, $numberOfJobs, $job);
        $jobQueue[] = false;

        $queue->method('reserve')->will($this->onConsecutiveCalls(...$jobQueue));

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

        $beanstalkdWrapper->method('getQueue')->willReturn($queue);

        $this->getObjectInstancier()->set(BeanstalkdWrapper::class, $beanstalkdWrapper);
        $this->getObjectInstancier()->set(RedisMutexWrapper::class, $redisWrapper);

        $IWorker = $this->getMockForAbstractClass(IWorker::class);
        $IWorker->method("getData")->willReturn([1]);
        /** @var IWorker $IWorker */

        $workerScript = $this->getObjectInstancier()->get(WorkerScript::class);
        return $workerScript->script($IWorker);
    }
}
