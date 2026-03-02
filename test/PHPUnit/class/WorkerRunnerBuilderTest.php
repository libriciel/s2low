<?php

use Malkusch\Lock\Mutex\Mutex;
use malkusch\lock\mutex\RedisMutex;
use Monolog\Level;
use Pheanstalk\Job;
use Pheanstalk\Pheanstalk;
use S2lowLegacy\Class\BeanstalkdWrapper;
use S2lowLegacy\Class\IWorker;
use S2lowLegacy\Class\RedisMutexWrapper;
use S2lowLegacy\Class\SigTermHandlerFactory;
use S2lowLegacy\Class\WorkerRunnerBuilder;
use S2lowLegacy\Class\JobFetcherFromDB;
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

        $mutexMock = $this->getMockBuilder(Mutex::class)
            ->disableOriginalConstructor()
            ->getMock();

        $redisWrapper->method('getMutex')->willReturn($mutexMock);

        $beanstalkdWrapper->method('getQueue')->willReturn($this->queue);

        $this->beanstalkdWrapper = $beanstalkdWrapper;
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
        $workerBuilder = $this->getWorkerRunnerBuilder();
        return $workerBuilder->script($IWorker)->work();
    }
    public function testBeanstalked()
    {
        $job = $this->getMockBuilder(Job::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertTrue($this->setUpWorkerBuilder($job));
        $this->assertTrue(
            $this->testHandler->hasRecord(
                'Travail en cours',
                Level::Info
            )
        );
    }

    public function testBeanstalkedisLimited()
    {
        $job = $this->getMockBuilder(Job::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertTrue($this->setUpWorkerBuilder($job, 200));
        $this->assertTrue(
            $this->testHandler->hasRecord(
                'Exit after 100 jobs executed',
                Level::Info
            )
        );
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
        $this->assertTrue(
            $this->testHandler->hasRecord(
                'foo',
                Level::Error
            )
        );
    }

    public function testScript()
    {
        $IWorker = $this->getMockForAbstractClass(IWorker::class);
        $IWorker->method("getAllId")->willReturn([1]);
        /** @var IWorker $IWorker */
        $workerBuilder = $this->getObjectInstancier()->get(WorkerRunnerBuilder::class);

        $this->assertTrue(
            $workerBuilder->scriptWithLogs($IWorker, false, JobFetcherFromDB::class)->work()
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

        $workerRunnerBuilder = $this->getWorkerRunnerBuilder();
        $this->assertTrue($workerRunnerBuilder->script($IWorker, JobFetcherFromDB::class)->work());

        $this->assertTrue(
            $this->testHandler->hasRecord(
                'SIGTERM reçu',
                Level::Notice
            )
        );
    }

    public function testScriptFailed()
    {
        $IWorker = $this->getMockForAbstractClass(IWorker::class);
        $IWorker
            ->method("getAllId")
            ->willThrowException(new Exception("foo"));
        /** @var IWorker $IWorker */

        $workerRunnerBuilder = $this->getWorkerRunnerBuilder();
        $this->assertFalse($workerRunnerBuilder->script($IWorker, JobFetcherFromDB::class)->work());

        $this->assertTrue(
            $this->testHandler->hasRecord(
                'Erreur lors de l\'execution du script : foo',
                Level::Error
            )
        );
    }

    private function getWorkerRunnerBuilder()
    {
        return new WorkerRunnerBuilder(
            $this->beanstalkdWrapper,
            $this->s2lowLogger,
            self::getContainer()->get(SigTermHandlerFactory::class),
            self::getContainer()->get(RedisMutexWrapper::class),
            self::getContainer()->get(WorkerScript::class),
        );
    }
}
