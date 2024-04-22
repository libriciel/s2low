<?php

declare(strict_types=1);

namespace S2low\Tests\Services\Helios;

use PHPUnit\Framework\TestCase;
use S2low\Services\Helios\DGFiPConnection\FTPFileRetrieveException;
use S2low\Services\Helios\FTPHeliosReceiver;
use S2low\Services\Helios\HeliosReceptionWorker;
use S2lowLegacy\Class\helios\HeliosAnalyseFichierRecuWorker;
use S2lowLegacy\Class\S2lowLogger;
use S2lowLegacy\Class\WorkerScript;

class HeliosReceptionWorkerTest extends TestCase
{
    private WorkerScript $workerScript;
    private FTPHeliosReceiver $FTPHeliosReceiver;
    protected function setUp(): void
    {
        parent::setUp();
        $logger = $this->getMockBuilder(S2lowLogger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->workerScript = $this->getMockBuilder(WorkerScript::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->FTPHeliosReceiver = $this->getMockBuilder(FTPHeliosReceiver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->heliosReceptionWorker = new HeliosReceptionWorker(
            $logger,
            $this->workerScript,
            $this->FTPHeliosReceiver
        );
    }

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

    public function testExecutionAvecFTPFileRetrieveException(): void
    {
        // Si l'exception FTPFileRetrieveException qui indique une erreur de récupération côté serveur est throw
        // lors de la récupération du fichier
        $this->FTPHeliosReceiver->expects(self::once())->method('recupOneFile')
            ->willThrowException(new FTPFileRetrieveException());
        // On n'aura rien à envoyer dans la queue d'analyse des fichiers reçus
        $this->workerScript->expects(self::never())->method('putJobByClassName')
            ->with(HeliosAnalyseFichierRecuWorker::class, 'fileName');
        // Mais on continuera le traitement des fichiers tout de même
        $this->FTPHeliosReceiver->expects(self::never())->method('finTraitement');

        $this->heliosReceptionWorker->work('fileName');
    }

    // Pour l'instant, il n'est pas possible de tester que le déclenchement d'un autre type d'exception stoppera bien
    // la réception ... Ce sera possible lorsqu'on changera le exit pour le changement d'une exception appropriée.
}
