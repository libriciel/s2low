<?php

use Monolog\Level;
use org\bovigo\vfs\vfsStream;
use S2low\Services\CloudFileStorageInterface;
use S2low\Services\LocalFileResolver;
use S2low\Services\RemoveStoredFilesOnDisk;
use S2lowLegacy\Class\helios\HeliosEnvoiSAE;
use S2lowLegacy\Class\helios\HeliosPrepareEnvoiSAE;
use S2lowLegacy\Class\helios\HeliosStatusSQL;
use S2lowLegacy\Class\helios\PESAllerCloudStorable;
use S2lowLegacy\Class\helios\PESAllerCloudStorage;
use S2lowLegacy\Class\PastellWrapperFactory;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use S2lowLegacy\Model\PastellPropertiesSQL;
use Symfony\Component\Filesystem\Filesystem;

class HeliosEnvoiSAETest extends S2lowTestCase
{
    use HeliosUtilitiesTestTrait;
    use PastellConfigurationTestTrait;

    private OpenStackSwiftWrapper $openStackSwiftWrapper;
    private HeliosEnvoiSAE $heliosEnvoiSAE;

    /**
     * @throws Exception
     */
    public function testSend()
    {
        $mockedPastellFactory = $this->mockPastellFactory();
        $heliosEnvoiSAE = $this->createHeliosEnvoiSae($mockedPastellFactory, true);
        $transaction_id = $this->setTransactionEnAttente();

        $this->assertFileExists($this->tmpPathFolder);

        $this->assertTrue(
            $heliosEnvoiSAE->sendArchive($transaction_id)
        );

        $this->assertFileExists($this->tmpPathFolder);

        $this->assertTrue(
            $this->testHandler->hasRecordThatContains(
                "Début du traitement de la transaction $transaction_id",
                Level::Info
            )
        );
        $this->assertTrue(
            $this->testHandler->hasRecordThatContains(
                "Début de la récupération des fichiers de la transaction $transaction_id",
                Level::Info
            )
        );
        $this->assertTrue(
            $this->testHandler->hasRecordThatContains(
                "Début de la récupération des fichiers de la transaction $transaction_id",
                Level::Info
            )
        );
        $this->assertTrue(
            $this->testHandler->hasRecordThatContains(
                "Début du transfert vers FakeURL de la transaction $transaction_id",
                Level::Info
            )
        );
        $this->assertTrue(
            $this->testHandler->hasRecordThatContains(
                "Suppression du fichier de la transaction $transaction_id",
                Level::Info
            )
        );
        $this->assertTrue(
            $this->testHandler->hasRecordThatContains(
                "La transaction $transaction_id a été envoyée à Pastell",
                Level::Info
            )
        );
    }

    private function setTransactionEnAttente(): int
    {
        $this->configurePastell();
        $transaction_id = $this->createTransaction();
        $this->getObjectInstancier()
            ->get(HeliosPrepareEnvoiSAE::class)
            ->setArchiveEnAttenteEnvoiSEA(1, $transaction_id);
        return $transaction_id;
    }

    /**
     * @throws Exception
     */
    public function testSendWithCloudError()
    {
        $heliosEnvoiSae = $this->createHeliosEnvoiSae(fileInCloud: true);
        $transaction_id = $this->setTransactionEnAttente();

        $fs = new Filesystem();
        $fs->remove($this->pesAllerPath);

        static::assertFalse(
            $heliosEnvoiSae->sendArchive($transaction_id)
        );

        $this->assertTrue(
            $this->testHandler->hasRecordThatContains(
                "Documents indisponibles pour la transaction $transaction_id  : Impossible de récupérer le PES ALLER ab3321d34d3fb32b52332befa534c9854fff677b",
                Level::Error
            )
        );
        $heliosTransactionsSQL = $this->getHeliosTransactionsSQL();
        $this->assertEquals(
            HeliosStatusSQL::STATUS_ERREUR_SAE_DOC_INDISPONIBLES,
            $heliosTransactionsSQL->getLatestStatusId($transaction_id)
        );
    }

    /**
     * @throws Exception
     */
    public function testSendTransactionEnErreur()
    {
        $mockPastell = $this->mockPastellFactory(false, "Erreur renvoyé par le mock");
        $heliosEnvoiSAE = $this->createHeliosEnvoiSae($mockPastell);
        $transaction_id = $this->setTransactionEnAttente();

        $this->assertFalse(
            $heliosEnvoiSAE->sendArchive($transaction_id)
        );

        $this->assertTrue(
            $this->testHandler->hasRecordThatContains(
                "La transaction $transaction_id n'a pas pu être envoyée sur Pastell : Erreur renvoyé par le mock",
                Level::Error
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testSendAllArchive()
    {
        $transaction_id = $this->setTransactionEnAttente();
        $mockPastell = $this->mockPastellFactory();
        $heliosEnvoiSAE = $this->createHeliosEnvoiSae($mockPastell);
        $heliosEnvoiSAE->sendAllArchive();

        $this->assertTrue(
            $this->testHandler->hasRecordThatContains(
                "1 transactions à envoyer...",
                Level::Info
            )
        );
        $this->assertTrue(
            $this->testHandler->hasRecordThatContains(
                "La transaction $transaction_id a été envoyée à Pastell",
                Level::Info
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testWhenErrorMessageIsTooLong()
    {
        $error_message = str_repeat('X', 1024);
        $mockPastell = $this->mockPastellFactory(false, $error_message);
        $heliosEnvoiSAE = $this->createHeliosEnvoiSae($mockPastell);
        $transaction_id = $this->setTransactionEnAttente();
        $this->assertFalse(
            $heliosEnvoiSAE->sendArchive($transaction_id)
        );
        $this->assertTrue(
            $this->testHandler->hasRecordThatContains(
                "La transaction $transaction_id n'a pas pu être envoyée sur Pastell : $error_message",
                Level::Error
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testErreurEnvoiSAE()
    {
        $heliosEnvoiSAE = $this->createHeliosEnvoiSae(null, true);
        $transaction_id = $this->setTransactionEnAttente();

        $this->assertFalse(
            $heliosEnvoiSAE->sendArchive($transaction_id)
        );

        $this->assertTrue(
            $this->testHandler->hasRecordThatContains(
                "La transaction $transaction_id n'a pas pu être envoyée sur Pastell : [Exception] Error Send SAE",
                Level::Error
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testErreurEnvoiSAEPuisErreurSuppression()
    {
        $transaction_id = $this->setTransactionEnAttente();
        $heliosEnvoiSAE = $this->createHeliosEnvoiSae();
        $this->assertFalse(
            $heliosEnvoiSAE->sendArchive($transaction_id)
        );

        self::assertTrue(
            $this->testHandler->hasRecordThatContains(
                "La transaction $transaction_id n'a pas pu être envoyée sur Pastell : [Exception] Error Send SAE et erreur lors de la suppression de dsf\[Exception] Error on delete",
                Level::Error
            )
        );
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->secondTmpPathFolder = vfsStream::url('test2');
        $this->pesAllerPath = $this->tmpPathFolder . "/ab3321d34d3fb32b52332befa534c9854fff677b";
        file_put_contents($this->pesAllerPath, "<test></test>");
        $this->openStackSwiftWrapper = $this->getOpenStackSwiftWrapperMocked();
    }

    private function getOpenStackSwiftWrapperMocked(): OpenStackSwiftWrapper
    {
        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackSwiftWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $openStackSwiftWrapper->method("fileExistsOnCloud")->willReturn(true);

        return $openStackSwiftWrapper;
    }

    private function createHeliosEnvoiSae(
        ?PastellWrapperFactory $mockPastellFactory = null,
        bool $fileInCloud = false,
    ): HeliosEnvoiSAE {
        $pastellWrapperFactory = $mockPastellFactory ?? $this->mockPastellFactory('dsf', "", true, true);
        $pastellPropertiesSQL = $this->getContainer()->get(PastellPropertiesSQL::class);

        $storePesAllerOnCloud = self::createMock(CloudFileStorageInterface::class);
        $storePesAllerOnCloud
            ->method('fileExistOnCloud')
            ->willReturn($fileInCloud);

        $removeStoredPesallerOnCloud = $this->getRemoveStoredfilesOnDisk($storePesAllerOnCloud);

        $localPesAllerResolver = self::createMock(LocalFileResolver::class);
        $localPesAllerResolver->method('getFullPath')->willReturn($this->pesAllerPath);

        $localPesAcquitResolver = self::getContainer()->get('app.localFileResolver.pes_acquit');
        $storePesAcquitOnCloud = self::getContainer()->get('app.store.file.pes_acquit');
        $removeStoredPesacquitOnCloud = self::getContainer()->get('app.removeFiles.pes_acquit');

        return new HeliosEnvoiSAE(
            $localPesAllerResolver,
            $localPesAcquitResolver,
            $storePesAllerOnCloud,
            $storePesAcquitOnCloud,
            $removeStoredPesallerOnCloud,
            $removeStoredPesacquitOnCloud,
            $pastellWrapperFactory,
            $this->logger,
            self::getContainer()->get(AuthoritySQL::class),
            $this->getHeliosTransactionsSQL(),
            $pastellPropertiesSQL,
        );
    }

    public function getHeliosTransactionsSQL(): HeliosTransactionsSQL
    {
        return self::getContainer()->get(HeliosTransactionsSQL::class);
    }

    private function getRemoveStoredfilesOnDisk(CloudFileStorageInterface $cloudFileStorage): RemoveStoredFilesOnDisk
    {
        return new RemoveStoredFilesOnDisk(
            $this->logger,
            (new Filesystem()),
            $cloudFileStorage,
            self::getContainer()->get(HeliosTransactionsSQL::class),
            self::getContainer()->get('app.localFileResolver.pes_aller'),
            self::getContainer()->get('app.finder.pes_aller'),
            self::getContainer()->getParameter('app.helios_files_upload_root'),
            self::getContainer()->getParameter('app.helios_repertoire_pes_aller_sans_transaction'),
            true
        );
    }
}
