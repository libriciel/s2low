<?php

declare(strict_types=1);

namespace PHPUnit\class;

use Pheanstalk\Exception\ServerException;
use Pheanstalk\Job;
use Pheanstalk\Pheanstalk;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use S2low\Services\Helios\HeliosReceptionWorker;
use S2lowLegacy\Class\CustomizableWorkerRunner;
use S2lowLegacy\Class\BeanstalkdWrapper;
use S2lowLegacy\Class\RedisMutexWrapper;
use S2lowLegacy\Class\S2lowLogger;
use S2lowLegacy\Class\JobFetcherFromSelfUpdatedBeanstalkd;
use S2lowLegacy\Class\WorkerScript;
use S2lowLegacy\Lib\SigTermHandler;

class WorkerRunnerWithSelfUpdatedBeanstalkdTest extends TestCase
{
    private HeliosReceptionWorker|MockObject $heliosReceptionWorker;
    private Job|MockObject $Job;
    private MockObject|Pheanstalk $queue;
    private MockObject|WorkerScript $workerScript;
    private CustomizableWorkerRunner $workerRunner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->heliosReceptionWorker =  $this->getMockBuilder(HeliosReceptionWorker::class)
            ->disableOriginalConstructor()
            ->getMock();

        $beanstalkdWrapper = $this->getMockBuilder(BeanstalkdWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->Job = $this->getMockBuilder(Job::class)->disableOriginalConstructor()->getMock();

        $this->queue = $this->getMockBuilder(Pheanstalk::class)->disableOriginalConstructor()->getMock();
        $beanstalkdWrapper->method('getQueue')->willReturn($this->queue);
        $sigTermHandler =  $this->getMockBuilder(SigTermHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->redisMutexWrapper =  $this->getMockBuilder(RedisMutexWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->workerScript = $this->getMockBuilder(WorkerScript::class)
            ->disableOriginalConstructor()
            ->getMock();

        $logger = $this->getMockBuilder(S2lowLogger::class)->disableOriginalConstructor()->getMock();

        $this->workerRunner = new CustomizableWorkerRunner(
            $this->heliosReceptionWorker,
            $logger,
            $sigTermHandler,
            0,
            new JobFetcherFromSelfUpdatedBeanstalkd(
                $beanstalkdWrapper,
                $this->workerScript,
                'queueName'
            )
        );
    }
    public function testNormalExecution(): void
    {
        // peekReady retourne un job : il n'y a pas besoin de rebuild la queue
        $this->queue->method('peekReady')->willReturn($this->Job);
        $this->workerScript->expects(static::never())->method('rebuildQueue');

        // La queue va renvoyer un seul job, puis false quand elle est vide
        $this->queue->method('reserve')->willReturnOnConsecutiveCalls($this->Job, false);
        $this->Job->expects(static::once())->method('getData')->willReturn('data');

        // Le job est supprimé systématiquement avant même d'être traité
        // Autrement, la queue risque d'être bloquée
        $this->queue->expects(self::once())->method('delete')->with($this->Job);

        // Le heliosReceptionWorker est capable de traiter
        $this->heliosReceptionWorker->expects(static::once())
            ->method('isDataValid')
            ->with('data')
            ->willReturn(true);
        $this->heliosReceptionWorker
            ->expects(static::once())
            ->method('work')
            ->with('data');

        $this->workerRunner->work();
    }

    public function testRebuildQueueWhenEmpty(): void
    {
        $this->queue->expects(static::once())->method('peekReady')
            ->willThrowException(new ServerException("Une ServerException est lancée quand aucun job n'est ready"));
        $this->workerScript->expects(static::once())->method('rebuildQueue');

        $this->workerRunner->work();
    }
}
