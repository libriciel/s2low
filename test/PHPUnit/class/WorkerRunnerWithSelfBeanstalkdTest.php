<?php

declare(strict_types=1);

namespace PHPUnit\class;

use Pheanstalk\Exception\ServerException;
use Pheanstalk\Job;
use Pheanstalk\Pheanstalk;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_TestCase;
use S2low\Services\Helios\HeliosReceptionWorker;
use S2lowLegacy\Class\BeanstalkdWrapper;
use S2lowLegacy\Class\IWorker;
use S2lowLegacy\Class\RedisMutexWrapper;
use S2lowLegacy\Class\S2lowLogger;
use S2lowLegacy\Class\WorkerRunnerWithSelfBeanstalkd;
use S2lowLegacy\Class\WorkerScript;
use S2lowLegacy\Lib\SigTermHandler;

class WorkerRunnerWithSelfBeanstalkdTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|(\S2low\Services\Helios\HeliosReceptionWorker&\PHPUnit\Framework\MockObject\MockObject)
     */
    private HeliosReceptionWorker|\PHPUnit\Framework\MockObject\MockObject $heliosReceptionWorker;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|(\S2lowLegacy\Class\BeanstalkdWrapper&\PHPUnit\Framework\MockObject\MockObject)
     */
    private BeanstalkdWrapper|\PHPUnit\Framework\MockObject\MockObject $beanstalkdWrapper;
    /**
     * @var (\Pheanstalk\Job&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private Job|\PHPUnit\Framework\MockObject\MockObject $Job;
    /**
     * @var (\Pheanstalk\Pheanstalk&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject|Pheanstalk $queue;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|(\S2lowLegacy\Lib\SigTermHandler&\PHPUnit\Framework\MockObject\MockObject)
     */
    private SigTermHandler|\PHPUnit\Framework\MockObject\MockObject $sigTermHandler;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|(\S2lowLegacy\Class\RedisMutexWrapper&\PHPUnit\Framework\MockObject\MockObject)
     */
    private \PHPUnit\Framework\MockObject\MockObject|RedisMutexWrapper $redisMutexWrapper;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|(\S2lowLegacy\Class\WorkerScript&\PHPUnit\Framework\MockObject\MockObject)
     */
    private \PHPUnit\Framework\MockObject\MockObject|WorkerScript $workerScript;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|(\S2lowLegacy\Class\S2lowLogger&\PHPUnit\Framework\MockObject\MockObject)
     */
    private S2lowLogger|\PHPUnit\Framework\MockObject\MockObject $logger;
    /**
     * @var \S2lowLegacy\Class\WorkerRunnerWithSelfBeanstalkd
     */
    private WorkerRunnerWithSelfBeanstalkd $workerRunner;

    protected function setUp(): void
    {
        $this->heliosReceptionWorker =  $this->getMockBuilder(HeliosReceptionWorker::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->beanstalkdWrapper = $this->getMockBuilder(BeanstalkdWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->Job = $this->getMockBuilder(Job::class)->disableOriginalConstructor()->getMock();

        $this->queue = $this->getMockBuilder(Pheanstalk::class)->disableOriginalConstructor()->getMock();
        $this->beanstalkdWrapper->method('getQueue')->willReturn($this->queue);
        $this->sigTermHandler =  $this->getMockBuilder(SigTermHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->redisMutexWrapper =  $this->getMockBuilder(RedisMutexWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->workerScript = $this->getMockBuilder(WorkerScript::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(S2lowLogger::class)->disableOriginalConstructor()->getMock();

        $this->workerRunner = new WorkerRunnerWithSelfBeanstalkd(
            $this->heliosReceptionWorker,
            $this->beanstalkdWrapper,
            $this->logger,
            $this->sigTermHandler,
            $this->redisMutexWrapper,
            $this->workerScript,
            0
        );
    }
    public function testNormalExecution()
    {
        // peekReady retourne un job : il n'y a pas besoin de rebuild la queue
        $this->queue->method('peekReady')->willReturn($this->Job);
        $this->workerScript->expects(static::never())->method('rebuildQueue');

        // La queue va renvoyer un seul job, puis false quand elle est vide
        $this->queue->method('reserve')->willReturnOnConsecutiveCalls($this->Job, false);
        $this->Job->expects(static::once())->method('getData')->willReturn("data");

        // Le heliosReceptionWorker est capable de traiter
        $this->heliosReceptionWorker->expects(static::once())
            ->method('getData')
            ->with('data')
            ->willReturn("data");
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

    //TODO : heu, Nope ??!!
    // il faudrait plutôt considérer que le job est traité.
    // si on le garde, il va bloquer la queue qui ne se rebuildera jamais !
    public function testJobIsReleasedOnException()
    {
        $this->heliosReceptionWorker->expects(static::once())->method('getData')->with('data')->willReturn("data");
        $this->heliosReceptionWorker->expects(static::once())->method('isDataValid')->with('data')->willReturn(true);
        $this->heliosReceptionWorker->expects(static::once())->method('work')->with('data')
            ->will(static::throwException(new \Exception("Oupsie !")));

        $this->Job->expects(static::once())->method('getData')->willReturn("data");

        $this->queue->method('peekReady')->willReturn($this->Job);
        $this->queue-> expects(self::once())->method('reserve')->willReturn($this->Job);
        $this->queue-> expects(self::once())->method('release');

        $this->workerRunner->work();
    }

    public function testRebuildQueueWhenEmpty()
    {
        $this->queue->expects(static::once())->method('peekReady')
            ->willThrowException(new ServerException("Une ServerException est lancée quand aucun job n'est ready"));
        $this->workerScript->expects(static::once())->method('rebuildQueue');

        $this->workerRunner->work();
    }
}
