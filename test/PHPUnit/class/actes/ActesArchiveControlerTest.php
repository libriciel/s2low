<?php

declare(strict_types=1);

namespace PHPUnit\class\actes;

use Exception;
use Monolog\Level;
use PastellConfigurationTestTrait;
use PHPUnit\ActesUtilitiesTestTrait;
use S2low\Services\CloudFileStorageInterface;
use S2lowLegacy\Class\actes\ActesArchiveControler;
use S2lowLegacy\Class\actes\ActesEnvelopeSQL;
use S2lowLegacy\Class\actes\ActesIncludedFileSQL;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\actes\ActesTypePJSQL;
use S2lowLegacy\Class\actes\ActeTamponne;
use S2lowLegacy\Class\PastellWrapperFactory;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\PastellPropertiesSQL;
use S2lowTestCase;

class ActesArchiveControlerTest extends S2lowTestCase
{
    use ActesUtilitiesTestTrait;
    use PastellConfigurationTestTrait;

    /**
     * @throws Exception
     */
    public function testSendArchiveWhenCreateOnPastellFailed(): void
    {
        $this->mockActesTamponne();

        $transaction_id = $this->createTransactionEnAttenteEnvoiSAE();
        $actesArchivesController = $this->createActesArchivesController(fileInCloud: true);
        $actesArchivesController->sendArchive($transaction_id);
        self::assertTrue(
            $this->testHandler->hasRecord(
                "Impossible d'envoyer la transaction $transaction_id : Erreur pastell : Erreur renvoyé par le mock",
                Level::Error
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testSendArchiveWhenNoRightStatus(): void
    {
        $transaction_id = $this->createTransaction(4);
        $this->createActesArchivesController()->sendArchive($transaction_id);

        $this->assertTrue(
            $this->testHandler->hasRecord(
                "La transaction $transaction_id à envoyer au SAE n'est pas dans le bon status ! 4 trouvé",
                Level::Error
            )
        );
    }

    private function createActesArchivesController(
        ?PastellWrapperFactory $pastellWrapperFactory = null,
        bool $fileInCloud = false
    ): ActesArchiveControler {
        $localFileResolver = self::getContainer()->get('app.localFileResolver.acte_enveloppe');
        $pastellPropertiesSQL = self::getContainer()->get(PastellPropertiesSQL::class);
        $pastellWrapperFactory = $pastellWrapperFactory ?? $this->mockPastellFactory(0, 'Erreur renvoyé par le mock');
        $authoritySQL = self::getContainer()->get(AuthoritySQL::class);
        $actesTransactionsSQL = self::getContainer()->get(ActesTransactionsSQL::class);
        $actesEnvelopeSQL = self::getContainer()->get(ActesEnvelopeSQL::class);
        $actesTypePJSQL = self::getContainer()->get(ActesTypePJSQL::class);
        $removeActeEnveloppeService = self::getContainer()->get('app.removeFiles.acte_enveloppe');
        $cloudFileStorage = self::createMock(CloudFileStorageInterface::class);
        $cloudFileStorage
            ->method('fileExistOnCloud')
            ->willReturn($fileInCloud);


        return new ActesArchiveControler(
            $localFileResolver,
            $cloudFileStorage,
            $pastellPropertiesSQL,
            $this->s2lowLogger,
            $pastellWrapperFactory,
            $authoritySQL,
            $actesTransactionsSQL,
            $actesEnvelopeSQL,
            $actesTypePJSQL,
            $removeActeEnveloppeService
        );
    }

    /**
     * @throws Exception
     */
    public function testSendArchiveCasNominal(): void
    {
        $transaction_id = $this->createTransactionEnAttenteEnvoiSAE();

        $controller = $this->createActesArchivesController($this->mockPastellFactory(), fileInCloud: true);
        $controller->sendArchive($transaction_id);
        $acteTransactionSQL = self::getContainer()->get(ActesTransactionsSQL::class);
        $last_status_info = $acteTransactionSQL->getLastStatusInfo($transaction_id);
        static::assertSame(
            ActesStatusSQL::STATUS_ENVOYE_AU_SAE,
            $last_status_info['status_id']
        );

        self::assertTrue(
            $this->testHandler->hasRecord(
                "La transaction $transaction_id a été envoyé sur le SAE (id_d pastell : xyzt)",
                Level::Info,
            ),
        );
    }

    /**
     * @throws Exception
     */
    public function testSendArchiveWithSpy(): void
    {
        // Arrange
        $this->mockActesTamponne();

        $projectDir = self::getContainer()->getParameter('kernel.project_dir');
        $arContent = file_get_contents($projectDir . '/test/PHPUnit/class/actes/fixtures/001-000000000-20170130-TEST42-DE-1-2_0.xml');

        $expectedActeContent = $this->extractFixtureFile(
            $projectDir . '/test/PHPUnit/class/actes/fixtures/abc-TACT--000000000--20170803-16.tar.gz',
            '034-000000000-20170801-20170803E-AI-1-1_1.pdf'
        );

        $capturedActeContent = null;
        $capturedARContent = null;

        $pastell = $this->getMockBuilder(\S2lowLegacy\Class\PastellWrapper::class)->disableOriginalConstructor()->getMock();
        $pastell->method('createActes')->willReturn("xyzt");
        $pastell->method('getLastError')->willReturn("");
        $pastell->method('sendSAE')->willReturn(true);

        $pastell->method('postActes')
            ->willReturnCallback(function ($id_d, $file_path, $file_name) use (&$capturedActeContent) {
                $capturedActeContent = file_get_contents($file_path);
                return true;
            });

        $pastell->method('postARActes')
            ->willReturnCallback(function ($id_d, $file_path) use (&$capturedARContent) {
                $capturedARContent = file_get_contents($file_path);
                return true;
            });

        $mockedPastellFactory = $this->getMockBuilder(PastellWrapperFactory::class)->disableOriginalConstructor()->getMock();
        $mockedPastellFactory->method('getNewInstance')->willReturn($pastell);

        $transaction_id = $this->createTransactionEnAttenteEnvoiSAE();

        $controller = $this->createActesArchivesController(
            pastellWrapperFactory: $mockedPastellFactory,
            fileInCloud: true
        );

        // Act
        $controller->sendArchive($transaction_id);

        // Assert
        $last_status_info = $this->getActesTransactionsSQL()->getLastStatusInfo($transaction_id);
        static::assertSame(
            ActesStatusSQL::STATUS_ENVOYE_AU_SAE,
            $last_status_info['status_id']
        );

        $this->assertNotNull($capturedActeContent);
        $this->assertNotNull($capturedARContent);
        $this->assertSame($expectedActeContent, $capturedActeContent);
        $this->assertSame($arContent, $capturedARContent);
    }

    /**
     * Helper to extract a file from a tar.gz archive to memory
     *
     * @throws Exception
     */
    private function extractFixtureFile(string $tarGzPath, string $filename): string
    {
        $tmpFolder = new \S2lowLegacy\Class\TmpFolder();
        $real_tmp = $tmpFolder->create();
        try {
            $tgzExtractor = new \S2lowLegacy\Class\TGZExtractor($real_tmp);
            $tgzExtractor->extract($tarGzPath, $filename);
            return file_get_contents($real_tmp . '/' . $filename);
        } finally {
            $tmpFolder->delete($real_tmp);
        }
    }

    /**
     * @throws \Exception
     */
    private function createTransactionEnAttenteEnvoiSAE(): int
    {
        $this->configurePastell();

        $projectDir = self::getContainer()->getParameter('kernel.project_dir');
        $transaction_id = $this->createTransaction(
            ActesStatusSQL::STATUS_POSTE,
            $projectDir . '/test/PHPUnit/class/actes/fixtures/abc-TACT--000000000--20170803-16.tar.gz'
        );
        /** @var ActesIncludedFileSQL $actesIncludedFileSQL */
        $actesIncludedFileSQL = self::getContainer()->get(ActesIncludedFileSQL::class);
        /** @var ActesTransactionsSQL $actesTransactionsSQL */
        $actesTransactionsSQL = self::getContainer()->get(ActesTransactionsSQL::class);

        $transaction_info = $actesTransactionsSQL->getInfo($transaction_id);

        $actesIncludedFileSQL->addIncludedFile(
            $transaction_info[ActesTransactionsSQL::ENVELOPE_ID],
            $transaction_id,
            'application/xml',
            772,
            '034-000000000-20170801-20170803E-AI-1-1_0.xml'
        );
        $actesIncludedFileSQL->addIncludedFile(
            $transaction_info[ActesTransactionsSQL::ENVELOPE_ID],
            $transaction_id,
            'application/pdf',
            101838,
            '034-000000000-20170801-20170803E-AI-1-1_1.pdf'
        );

        $actesTransactionsSQL->updateStatus(
            $transaction_id,
            ActesStatusSQL::STATUS_ACQUITTEMENT_RECU,
            'test',
            file_get_contents(
                $projectDir . '/test/PHPUnit/class/actes/fixtures/001-000000000-20170130-TEST42-DE-1-2_0.xml'
            )
        );
        $actesTransactionsSQL->updateStatus(
            $transaction_id,
            ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE,
            'test'
        );
        return $transaction_id;
    }

    /**
     * @throws Exception
     */
    public function testgetAllTransactionIdToSend(): void
    {
        $this->configurePastell();
        $transaction_id = $this->createTransactionEnAttenteEnvoiSAE();
        $all = $this->getActesArchivesControler()->getAllTransactionIdToSend();
        static::assertSame([$transaction_id], $all);
    }

    private function getActesArchivesControler(): ActesArchiveControler
    {
        return $this->getObjectInstancier()->get(ActesArchiveControler::class);
    }

    /**
     * @throws Exception
     */
    public function testSendArchiveWhenActesRetrieverFailed()
    {
        $this->mockPastellFactory();
        $transaction_id = $this->createTransactionEnAttenteEnvoiSAE();

        $actesArchivesController = $this->createActesArchivesController();
        $actesArchivesController->sendArchive($transaction_id);

        /** @var ActesTransactionsSQL $actesTransactionsSQL */
        $actesTransactionsSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);

        $last_status_info = $actesTransactionsSQL->getLastStatusInfo($transaction_id);
        static::assertSame(ActesStatusSQL::STATUS_ERREUR_SAE_DOC_INDISPONIBLES, $last_status_info['status_id']);

        $this->assertTrue(
            $this->testHandler->hasRecord(
                "Documents indisponibles pour la transaction $transaction_id  : Impossible de récupérer l'enveloppe abc-TACT--000000000--20170803-16.tar.gz",
                Level::Error
            )
        );
    }

    public function getActesTransactionsSQL(): ActesTransactionsSQL
    {
        return $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
    }

    private function mockActesTamponne(): ActeTamponne
    {
        $acteTamponne = $this->getMockBuilder(ActeTamponne::class)->disableOriginalConstructor()->getMock();
        $acteTamponne->method('tamponnerPDF')->willReturn(
            file_get_contents(__DIR__ . '/fixtures/convention-exemple.pdf')
        );
        self::getContainer()->set(ActeTamponne::class, $acteTamponne);

        return $acteTamponne;
    }
}
