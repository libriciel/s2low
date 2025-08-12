<?php

declare(strict_types=1);

namespace PHPUnit\class;

use Error;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Pheanstalk\Exception\ServerException;
use Pheanstalk\Job;
use Pheanstalk\Pheanstalk;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Exception;
use S2low\Services\Helios\HeliosReceptionWorker;
use S2lowLegacy\Class\CustomizableWorkerRunner;
use S2lowLegacy\Class\BeanstalkdWrapper;
use S2lowLegacy\Class\RecoverableException;
use S2lowLegacy\Class\RedisMutexWrapper;
use S2lowLegacy\Class\S2lowLogger;
use S2lowLegacy\Class\JobFetcherFromSelfUpdatedBeanstalkd;
use S2lowLegacy\Class\WorkerScript;
use S2lowLegacy\Class\WorkerScriptException;
use S2lowLegacy\Lib\PausingQueueException;
use S2lowLegacy\Lib\SigTermHandler;

class CustomizableWorkerRunnerTest extends TestCase
{
    private HeliosReceptionWorker|MockObject $heliosReceptionWorker;
    private Job|MockObject $Job;
    private MockObject|Pheanstalk $queue;
    private MockObject|WorkerScript $workerScript;
    private CustomizableWorkerRunner $workerRunner;
    private TestHandler $testHandler;

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

        $this->testHandler = new TestHandler();
        $logger = new Logger('tests');
        $logger->pushHandler($this->testHandler);


        $this->workerRunner = new CustomizableWorkerRunner(
            $this->heliosReceptionWorker,
            new S2lowLogger($logger),
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

    public function testExecutionWhithFinallyException(): void
    {
        $this->queue->method('peekReady')->willReturn($this->Job);

        $this->queue->method('reserve')->willReturnOnConsecutiveCalls($this->Job, false);
        $this->Job->method('getData')->willReturn('data');

        // Le heliosReceptionWorker est capable de traiter
        $this->heliosReceptionWorker->method('isDataValid')->willReturn(true);
        $this->workerRunner->setMinExecutionTimeInSeconds(1);
        $this->workerRunner->work();
        static::assertTrue(
            $this->testHandler->hasInfoThatMatches('/Arret du script/')
        );
    }

    public function testRecoverableException(): void
    {
        // peekReady retourne un job : il n'y a pas besoin de rebuild la queue
        $this->queue->method('peekReady')->willReturn($this->Job);
        $this->workerScript->expects(static::never())->method('rebuildQueue');

        // La queue va renvoyer un seul job, puis false quand elle est vide
        $this->queue->method('reserve')->willReturnOnConsecutiveCalls($this->Job, $this->Job);

        $this->Job->expects(static::exactly(2))->method('getData')->willReturn('data');

        // Le job est supprimé systématiquement avant même d'être traité
        // Autrement, la queue risque d'être bloquée
        $this->queue->expects(self::exactly(2))->method('delete')->with($this->Job);

        // Le heliosReceptionWorker est capable de traiter les deux job
        $this->heliosReceptionWorker->expects(self::exactly(2))
            ->method('isDataValid')
            ->with('data')
            ->willReturn(true);

        // mais rencontre une RecoverableException la première fois, et traite correctement la seconde
        $matcher     = static::exactly(2);
        $this->heliosReceptionWorker
            ->expects(static::exactly(2))
            ->method('work')
            ->willReturnCallback(function () use ($matcher) {
                if ($matcher->getInvocationCount() === 0) {
                    throw new RecoverableException();
                }
                return 'data';
            });

        $this->workerRunner->work();
    }

    public function testRebuildQueueWhenEmpty(): void
    {
        $this->queue->expects(static::once())->method('peekReady')
            ->willThrowException(new ServerException("Une ServerException est lancée quand aucun job n'est ready"));
        $this->workerScript->expects(static::once())->method('rebuildQueue');

        $this->workerRunner->work();
    }

    public function testWorkerScriptException(): void
    {
        // peekReady retourne un job : il n'y a pas besoin de rebuild la queue
        $this->queue->method('peekReady')->willReturn($this->Job);
        $this->workerScript->expects(static::never())->method('rebuildQueue');

        // La queue va renvoyer un seul job, puis false quand elle est vide
        $this->queue->method('reserve')->willReturnOnConsecutiveCalls($this->Job, $this->Job);

        $this->Job->expects(static::exactly(1))->method('getData')->willReturn('data');

        // Le job est supprimé
        $this->queue->expects(self::exactly(1))->method('delete')->with($this->Job);

        // Le heliosReceptionWorker ne traite qu'un job
        $this->heliosReceptionWorker->expects(self::exactly(1))
            ->method('isDataValid')
            ->with('data')
            ->willReturn(true);

        // car il rencontre une WorkerScriptException la première fois, et ne traite pas la seconde
        $matcher     = static::exactly(2);
        $this->heliosReceptionWorker
            ->expects(static::exactly(1))
            ->method('work')
            ->willReturnCallback(function () use ($matcher) {
                if ($matcher->getInvocationCount() === 0) {
                    throw new WorkerScriptException('Un message informatif');
                }
                return 'data';
            });

        static::assertTrue($this->workerRunner->work());
    }

    public function testPausingQueueException(): void
    {
        // peekReady retourne un job : il n'y a pas besoin de rebuild la queue
        $this->queue->method('peekReady')->willReturn($this->Job);
        $this->workerScript->expects(static::never())->method('rebuildQueue');

        // La queue va renvoyer un seul job, puis false quand elle est vide
        $this->queue->method('reserve')->willReturnOnConsecutiveCalls($this->Job, $this->Job);

        $this->Job->expects(static::exactly(1))->method('getData')->willReturn('data');

        // Le job est supprimé
        $this->queue->expects(self::exactly(1))->method('delete')->with($this->Job);

        // Le heliosReceptionWorker ne traite qu'un job
        $this->heliosReceptionWorker->expects(self::exactly(1))
            ->method('isDataValid')
            ->with('data')
            ->willReturn(true);

        // car il rencontre une PausingQueueException la première fois, et ne traite pas la seconde
        $matcher     = static::exactly(2);
        $this->heliosReceptionWorker
            ->expects(static::exactly(1))
            ->method('work')
            ->willReturnCallback(function () use ($matcher) {
                if ($matcher->getInvocationCount() === 0) {
                    throw new PausingQueueException('Un message informatif');
                }
                return 'data';
            });

        static::assertTrue($this->workerRunner->work());
        static::assertTrue(
            $this->testHandler->hasInfoThatMatches('/Pausing queue for 30 seconds/')
        );
    }

    public function testThrowable(): void
    {
        // peekReady retourne un job : il n'y a pas besoin de rebuild la queue
        $this->queue->method('peekReady')->willReturn($this->Job);
        $this->workerScript->expects(static::never())->method('rebuildQueue');

        // La queue va renvoyer un seul job, puis false quand elle est vide
        $this->queue->method('reserve')->willReturnOnConsecutiveCalls($this->Job, $this->Job);

        $this->Job->expects(static::exactly(1))->method('getData')->willReturn('data');

        // Le job est supprimé
        $this->queue->expects(self::exactly(1))->method('delete')->with($this->Job);

        // Le heliosReceptionWorker ne traite qu'un job
        $this->heliosReceptionWorker->expects(self::exactly(1))
            ->method('isDataValid')
            ->with('data')
            ->willReturn(true);

        // car il rencontre une PausingQueueException la première fois, et ne traite pas la seconde
        $matcher     = static::exactly(2);
        $this->heliosReceptionWorker
            ->expects(static::exactly(1))
            ->method('work')
            ->willReturnCallback(function () use ($matcher) {
                if ($matcher->getInvocationCount() === 0) {
                    throw new Error('Un message informatif');
                }
                return 'data';
            });

        $this->workerRunner->setMinExecutionTimeInSeconds(1);
        static::assertFalse($this->workerRunner->work());
        static::assertTrue(
            $this->testHandler->hasCriticalThatContains("Erreur lors de l'execution du script : Un message informatif")
        );
        static::assertTrue(
            $this->testHandler->hasInfoThatMatches('/Arret du script/')
        );
    }
}
