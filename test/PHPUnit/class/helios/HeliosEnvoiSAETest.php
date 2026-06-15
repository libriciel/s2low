<?php

use Monolog\Level;
use org\bovigo\vfs\vfsStream;
use S2low\Services\CloudFileStorageInterface;
use S2low\Services\LocalFileResolver;
use S2low\Services\RemoveStoredFilesOnDisk;
use S2lowLegacy\Class\helios\HeliosEnvoiSAE;
use S2lowLegacy\Class\helios\HeliosPrepareEnvoiSAE;
use S2lowLegacy\Class\helios\HeliosStatusSQL;
use S2lowLegacy\Class\PastellWrapper;
use S2lowLegacy\Class\PastellWrapperFactory;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use S2lowLegacy\Model\PastellPropertiesSQL;
use Symfony\Component\Filesystem\Filesystem;

class HeliosEnvoiSAETest extends S2lowTestCase
{
    use HeliosUtilitiesTestTrait;
    use PastellConfigurationTestTrait;

    private HeliosTransactionsSQL $heliosTransactionsSQL;
    private string $pesAllerPath;
    protected string $secondTmpPathFolder;

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

    /**
     * @throws Exception
     */
    public function testSendWithSpyAndAcquit(): void
    {
        // Arrange
        $pesAllerContent = file_get_contents(__DIR__ . '/../../helios/fixtures/pes_aller.xml');
        $pesAcquitContent = file_get_contents(__DIR__ . '/../../helios/fixtures/pes_acquit.xml');

        // Put files in the expected vfsStream paths
        file_put_contents($this->tmpPathFolder . "/ab3321d34d3fb32b52332befa534c9854fff677b", $pesAllerContent);

        file_put_contents($this->secondTmpPathFolder . '/pes_acquit.xml', $pesAcquitContent);

        $capturedPesAllerContent = null;
        $capturedPesAcquitContent = null;

        $pastell = $this->getMockBuilder(PastellWrapper::class)->disableOriginalConstructor()->getMock();
        $pastell->method('createHelios')->willReturn("xyzt");
        $pastell->method('getLastError')->willReturn("");
        $pastell->method('sendSAE')->willReturn(true);
        $pastell->method('postFile')
            ->willReturnCallback(function ($id_d, $field, $file_path, $file_orig_name) use (&$capturedPesAllerContent, &$capturedPesAcquitContent) {
                if ($field === 'fichier_pes') {
                    $capturedPesAllerContent = file_get_contents($file_path);
                } elseif ($field === 'fichier_reponse') {
                    $capturedPesAcquitContent = file_get_contents($file_path);
                }
                return true;
            });

        $mockedPastellFactory = $this->getMockBuilder(PastellWrapperFactory::class)->disableOriginalConstructor()->getMock();
        $mockedPastellFactory->method('getNewInstance')->willReturn($pastell);

        $transaction_id = $this->setTransactionEnAttente();
        $this->heliosTransactionsSQL->setAcquitFilename($transaction_id, 'pes_acquit.xml');

        $heliosEnvoiSAE = $this->createHeliosEnvoiSae($mockedPastellFactory);

        // Act
        $result = $heliosEnvoiSAE->sendArchive($transaction_id);

        // Assert
        $this->assertTrue($result);
        $this->assertNotNull($capturedPesAllerContent);
        $this->assertNotNull($capturedPesAcquitContent);
        $this->assertSame($pesAllerContent, $capturedPesAllerContent);
        $this->assertSame($pesAcquitContent, $capturedPesAcquitContent);
        $this->assertNotSame($capturedPesAllerContent, $capturedPesAcquitContent);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->heliosTransactionsSQL = $this->getHeliosTransactionsSQL();
        $this->secondTmpPathFolder = $this->tmpPathFolder . '/test2';
        if (!is_dir($this->secondTmpPathFolder)) {
            mkdir($this->secondTmpPathFolder);
        }
        $this->pesAllerPath = $this->tmpPathFolder . "/ab3321d34d3fb32b52332befa534c9854fff677b";
        file_put_contents($this->pesAllerPath, "<test></test>");
    }

    private function createHeliosEnvoiSae(
        ?PastellWrapperFactory $mockPastellFactory = null,
        bool $fileInCloud = false,
        ?LocalFileResolver $localPesAcquitResolver = null,
    ): HeliosEnvoiSAE {
        $pastellWrapperFactory = $mockPastellFactory ?? $this->mockPastellFactory('dsf', "", true, true);
        $pastellPropertiesSQL = $this->getContainer()->get(PastellPropertiesSQL::class);

        $storePesAllerOnCloud = self::createMock(CloudFileStorageInterface::class);
        $storePesAllerOnCloud
            ->method('fileExistOnCloud')
            ->willReturn($fileInCloud);

        $localPesAllerResolver = self::createMock(LocalFileResolver::class);
        $localPesAllerResolver->method('getFullPath')->willReturn($this->pesAllerPath);

        $removeStoredPesallerOnCloud = $this->getRemoveStoredFilesOnDisk($storePesAllerOnCloud, $localPesAllerResolver);

        if ($localPesAcquitResolver === null) {
            $localPesAcquitResolver = self::createMock(LocalFileResolver::class);
            $localPesAcquitResolver->method('getFullPath')->willReturn($this->secondTmpPathFolder . '/pes_acquit.xml');
        }

        $storePesAcquitOnCloud = self::createMock(CloudFileStorageInterface::class);
        $storePesAcquitOnCloud
            ->method('fileExistOnCloud')
            ->willReturn($fileInCloud);

        $removeStoredPesacquitOnCloud = $this->getRemoveStoredFilesOnDisk($storePesAcquitOnCloud, $localPesAcquitResolver);

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

    private function getRemoveStoredFilesOnDisk(
        CloudFileStorageInterface $cloudFileStorage,
        LocalFileResolver $localFileResolver,
    ): RemoveStoredFilesOnDisk {
        return new RemoveStoredFilesOnDisk(
            $this->logger,
            new Filesystem(),
            $cloudFileStorage,
            self::getContainer()->get(HeliosTransactionsSQL::class),
            $localFileResolver,
            self::createMock(\Symfony\Component\Finder\Finder::class),
            '/tmp',
            '/tmp',
            true
        );
    }
}
