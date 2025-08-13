<?php

declare(strict_types=1);

namespace S2low\Tests\Services\Helios;

use Exception;
use RuntimeException;
use PHPUnit\Framework\TestCase;
use S2low\Services\Helios\DGFiPConnection\FTPFileRetrieveException;
use S2low\Services\Helios\FTPHeliosReceiver;
use S2low\Services\Helios\FTPHeliosReceiverLifecycle;
use S2low\Services\Helios\HeliosReceptionWorker;
use S2lowLegacy\Class\helios\HeliosAnalyseFichierRecuWorker;
use S2lowLegacy\Class\RecoverableException;
use S2lowLegacy\Class\S2lowLogger;
use S2lowLegacy\Class\WorkerScript;

class HeliosReceptionWorkerTest extends TestCase
{
    private S2lowLogger $logger;
    private WorkerScript $workerScript;
    private FTPHeliosReceiver $FTPHeliosReceiver;
    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = $this->getMockBuilder(S2lowLogger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->workerScript = $this->getMockBuilder(WorkerScript::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->FTPHeliosReceiver = $this->getMockBuilder(FTPHeliosReceiver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->FTPHeliosReceiverManager = $this->getMockBuilder(FTPHeliosReceiverLifecycle::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->FTPHeliosReceiverManager->method('get')->willReturn($this->FTPHeliosReceiver);


        $this->heliosReceptionWorker = new HeliosReceptionWorker(
            $this->logger,
            $this->workerScript,
            $this->FTPHeliosReceiverManager
        );
    }

    /**
     * @throws Exception
     */
    public function testExecutionNormale(): void
    {
        // On récupère bien le fichier fileName
        $this->FTPHeliosReceiver->expects(self::once())->method('recupOneFile')->with('fileName');
        // Et il est mis dans la queue des fichiers à analyser
        $this->workerScript->expects(self::once())->method('putJobByClassName')
            ->with(HeliosAnalyseFichierRecuWorker::class, 'fileName');
        // et le traitement des fichiers continue
        $this->FTPHeliosReceiver->expects(self::never())->method('finTraitement');

        $this->heliosReceptionWorker->work('fileName');
    }

    /**
     * @throws Exception
     */
    public function testExecutionAvecFTPFileRetrieveException(): void
    {
        // Si l'exception FTPFileRetrieveException qui indique une erreur de récupération côté serveur est throw
        // lors de la récupération du fichier
        $this->FTPHeliosReceiver->expects(self::once())->method('recupOneFile')
            ->willThrowException(new FTPFileRetrieveException('OupsieDaysy'));
        // On n'aura rien à envoyer dans la queue d'analyse des fichiers reçus
        $this->workerScript->expects(self::never())->method('putJobByClassName')
            ->with(HeliosAnalyseFichierRecuWorker::class, 'fileName');
        // Et une RecoverableException est lancée
        $this->expectException(RecoverableException::class);
        $this->expectExceptionMessage('OupsieDaysy');

        $this->heliosReceptionWorker->work('fileName');
    }

    /**
     * @throws Exception
     */
    public function testExecutionAvecAutreException(): void
    {
        // Si une Exception générique est throw
        // lors de la récupération du fichier
        $this->FTPHeliosReceiver->expects(self::once())->method('recupOneFile')
            ->willThrowException(new RuntimeException('OupsieDaysy'));
        // On n'aura rien à envoyer dans la queue d'analyse des fichiers reçus
        $this->workerScript->expects(self::never())->method('putJobByClassName')
            ->with(HeliosAnalyseFichierRecuWorker::class, 'fileName');
        // Et l'exception est relancée
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OupsieDaysy');

        $this->heliosReceptionWorker->work('fileName');
    }

    /**
     * @dataProvider queueNamesProvider
     */
    public function testQueueName(bool $usePasstrans, string $queueName): void
    {
        static::assertEquals(
            $queueName,
            (new HeliosReceptionWorker(
                $this->logger,
                $this->workerScript,
                $this->FTPHeliosReceiverManager,
                $usePasstrans
            ))->getQueueName()
        );
    }

    public function queueNamesProvider(): iterable
    {
        yield [false, HeliosReceptionWorker::QUEUE_NAME];
        yield [true, HeliosReceptionWorker::QUEUE_NAME . '-passtrans'];
    }
}
