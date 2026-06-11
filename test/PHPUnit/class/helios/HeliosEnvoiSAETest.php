<?php

use Monolog\Level;
use org\bovigo\vfs\vfsStream;
use S2lowLegacy\Class\helios\HeliosEnvoiSAE;
use S2lowLegacy\Class\helios\HeliosPrepareEnvoiSAE;
use S2lowLegacy\Class\helios\HeliosStatusSQL;
use S2lowLegacy\Class\helios\PESAcquitCloudStorable;
use S2lowLegacy\Class\helios\PESAcquitCloudStorage;
use S2lowLegacy\Class\helios\PESAllerCloudStorable;
use S2lowLegacy\Class\helios\PESAllerCloudStorage;
use S2lowLegacy\Class\helios\PesAllerRetriever;
use S2lowLegacy\Class\PastellWrapper;
use S2lowLegacy\Class\PastellWrapperFactory;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use S2lowLegacy\Model\PastellPropertiesSQL;

class HeliosEnvoiSAETest extends S2lowTestCase
{
    use HeliosUtilitiesTestTrait;
    use PastellConfigurationTestTrait;

    private OpenStackSwiftWrapper $openStackSwiftWrapper;
    private HeliosEnvoiSAE $heliosEnvoiSAE;
    private HeliosTransactionsSQL $heliosTransactionsSQL;

    /**
     * @throws Exception
     */
    public function testSend()
    {
        $mockedPastellFactory = $this->mockPastellFactory();
        $heliosEnvoiSAE = $this->createHeliosEnvoiSae($mockedPastellFactory);
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
            $this->testHandler->hasRecordThatMatches(
                "/Deleting object #$transaction_id/",
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
        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackSwiftWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $openStackSwiftWrapper->method("fileExistsOnCloud")->willReturn(true);
        $openStackSwiftWrapper->method("retrieveFile")->willReturn('');

        $heliosEnvoiSae = $this->createHeliosEnvoiSae(openStackSwiftWrapper: $openStackSwiftWrapper);
        $transaction_id = $this->setTransactionEnAttente();

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
        $transaction_id = $this->setTransactionEnAttente();

        $this->assertFalse(
            $this->heliosEnvoiSAE->sendArchive($transaction_id)
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
        $this->assertFalse(
            $this->heliosEnvoiSAE->sendArchive($transaction_id)
        );

        $this->testHandler->hasRecordThatContains(
            "La transaction $transaction_id n'a pas pu être envoyée sur Pastell : [Exception] Error Send SAE et erreur lors de la suppression de dsf\[Exception] Error on delete",
            Level::Error
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

        $this->secondTmpPathFolder = $this->tmpPathFolder . '/test2';
        mkdir($this->secondTmpPathFolder);
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
        $this->assertNotEquals($capturedPesAllerContent, $capturedPesAcquitContent);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->heliosTransactionsSQL = $this->getHeliosTransactionsSQL();
        $this->secondTmpPathFolder = vfsStream::url('test2');
        $pesAllerPath = $this->tmpPathFolder . "/ab3321d34d3fb32b52332befa534c9854fff677b";
        file_put_contents($pesAllerPath, "<test></test>");

        $this->openStackSwiftWrapper = $this->getOpenStackSwiftWrapperMocked($pesAllerPath);
        $this->heliosEnvoiSAE = $this->createHeliosEnvoiSae();
    }

    private function getOpenStackSwiftWrapperMocked($pesAllerPath): OpenStackSwiftWrapper
    {
        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackSwiftWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $openStackSwiftWrapper->method("fileExistsOnCloud")->willReturn(true);
        $openStackSwiftWrapper->method("retrieveFile")->willReturn($pesAllerPath);

        return $openStackSwiftWrapper;
    }

    private function createHeliosEnvoiSae(
        ?PastellWrapperFactory $mockPastellFactory = null,
        ?OpenStackSwiftWrapper $openStackSwiftWrapper = null,
    ): HeliosEnvoiSAE {
        $pesAllerRetriever = $this->getPesAllerRetriever($openStackSwiftWrapper);
        $pastellWrapperFactory = $mockPastellFactory ?? $this->mockPastellFactory('dsf', "", true, true);
        $pastellPropertiesSQL = $this->getContainer()->get(PastellPropertiesSQL::class);

        $pesAllerCloudStorable = new PesAllerCloudStorable(
            $this->tmpPathFolder,
            self::getContainer()->get(HeliosTransactionsSQL::class),
            $this->secondTmpPathFolder,
        );
        $pesAllerCloudStorage = new PesAllerCloudStorage(
            $pesAllerCloudStorable,
            $openStackSwiftWrapper ?? $this->openStackSwiftWrapper,
            $this->logger,
            openstack_enable: false
        );

        $pesAcquitCloudStorable = new PesAcquitCloudStorable(
            self::getContainer()->get(HeliosTransactionsSQL::class),
            $this->secondTmpPathFolder,
        );
        $pesAcquitCloudStorage = new PesAcquitCloudStorage(
            $pesAcquitCloudStorable,
            $openStackSwiftWrapper ?? $this->openStackSwiftWrapper,
            $this->logger,
            openstack_enable: false
        );

        return new HeliosEnvoiSAE(
            $pesAllerRetriever,
            $pastellWrapperFactory,
            $this->logger,
            self::getContainer()->get(AuthoritySQL::class),
            $this->getHeliosTransactionsSQL(),
            $pastellPropertiesSQL,
            $pesAcquitCloudStorage,
            $pesAllerCloudStorage
        );
    }

    private function getPesAllerRetriever(?OpenStackSwiftWrapper $openStackSwiftWrapper = null): PesAllerRetriever
    {
        return new PesAllerRetriever(
            $this->tmpPathFolder,
            $openStackSwiftWrapper ?? $this->openStackSwiftWrapper,
            $this->s2lowLogger,
        );
    }

    public function getHeliosTransactionsSQL(): HeliosTransactionsSQL
    {
        return self::getContainer()->get(HeliosTransactionsSQL::class);
    }
}
