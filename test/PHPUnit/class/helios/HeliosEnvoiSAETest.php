<?php

use S2lowLegacy\Class\helios\HeliosEnvoiSAE;
use S2lowLegacy\Class\helios\HeliosPrepareEnvoiSAE;
use S2lowLegacy\Class\helios\HeliosStatusSQL;
use S2lowLegacy\Class\helios\IWorkspace;
use S2lowLegacy\Class\TmpFolder;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;
use S2lowLegacy\Model\HeliosTransactionsSQL;

class HeliosEnvoiSAETest extends S2lowTestCase
{
    use HeliosUtilitiesTestTrait;
    use PastellConfigurationTestTrait;

    public function getHeliosTransactionsSQL(): HeliosTransactionsSQL
    {
        return $this->getObjectInstancier()->get(HeliosTransactionsSQL::class);
    }

    /**
     * @return string
     * @throws Exception
     */
    private function mockOpenStack()
    {

        $pes_aller_path = $this->getObjectInstancier()
                ->get(IWorkspace::class)->getHeliosFilesUploadRoot() . "/ab3321d34d3fb32b52332befa534c9854fff677b";
        file_put_contents($pes_aller_path, "<test></test>");
        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackSwiftWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $openStackSwiftWrapper->method("fileExistsOnCloud")->willReturn(true);
        $openStackSwiftWrapper->method("retrieveFile")->willReturn($pes_aller_path);
        $this->getObjectInstancier()->set(OpenStackSwiftWrapper::class, $openStackSwiftWrapper);

        return $pes_aller_path;
    }

    /**
     * @throws Exception
     */
    public function testSend()
    {
        $pes_aller_path = $this->mockOpenStack();
        $this->mockPastellFactory();
        $transaction_id = $this->setTransactionEnattente();

        $this->assertFileExists($pes_aller_path);

        $this->assertTrue(
            $this->getObjectInstancier()->get(HeliosEnvoiSAE::class)->sendArchive($transaction_id)
        );

        $this->assertFileDoesNotExist($pes_aller_path);
        $this->assertLogMessage("Début du traitement de la transaction $transaction_id", 1);
        $this->assertLogMessage("Début de la récupération des fichiers de la transaction $transaction_id", 2);
        $this->assertLogMessage("Début du transfert vers FakeURL de la transaction $transaction_id", 3);
        $this->assertMatchesRegularExpressionLogMessage("/Deleting object #$transaction_id/", 4);
        $this->assertLogMessage("La transaction $transaction_id a été envoyée à Pastell", 5);
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
        $this->getObjectInstancier()->set(OpenStackSwiftWrapper::class, $openStackSwiftWrapper);
        $this->getObjectInstancier()->set('helios_files_upload_root', '/whatever/');

        $this->mockPastellFactory();
        $transaction_id = $this->setTransactionEnattente();

        static::assertFalse(
            $this->getObjectInstancier()->get(HeliosEnvoiSAE::class)->sendArchive($transaction_id)
        );

        $this->assertLogMessage(
            "Documents indisponibles pour la transaction $transaction_id  : Impossible de récupérer le PES ALLER ab3321d34d3fb32b52332befa534c9854fff677b",
            3
        );

        /** @var HeliosTransactionsSQL $heliosTransactionsSQL */
        $heliosTransactionsSQL = $this->getObjectInstancier()->get(HeliosTransactionsSQL::class);
        $this->assertEquals(
            HeliosStatusSQL::STATUS_ERREUR_SAE_DOC_INDISPONIBLES,
            $heliosTransactionsSQL->getLatestStatusId($transaction_id)
        );
    }

    private function setTransactionEnattente()
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
    public function testSendTransactionEnErreur()
    {
        $this->mockOpenStack();

        $this->mockPastellFactory(false, "Erreur renvoyé par le mock");
        $transaction_id = $this->setTransactionEnattente();

        $this->assertFalse(
            $this->getObjectInstancier()->get(HeliosEnvoiSAE::class)->sendArchive($transaction_id)
        );
        $this->assertLogMessage(
            "La transaction $transaction_id n'a pas pu être envoyée sur Pastell : Erreur renvoyé par le mock",
            4
        );
    }

    /**
     * @throws Exception
     */
    public function testSendAllArchive()
    {
        $this->mockOpenStack();
        $this->mockPastellFactory("xyzt", false);
        $transaction_id = $this->setTransactionEnattente();
        $this->getObjectInstancier()->get(HeliosEnvoiSAE::class)->sendAllArchive();
        $this->assertLogMessage("1 transactions à envoyer...", 2);
        $this->assertLogMessage("La transaction $transaction_id a été envoyée à Pastell", 8);
    }

    /**
     * @throws Exception
     */
    public function testWhenErrorMessageIsTooLong()
    {
        $this->mockOpenStack();
        $error_message = str_repeat('X', 1024);
        $this->mockPastellFactory(false, $error_message);
        $transaction_id = $this->setTransactionEnattente();
        $this->assertFalse(
            $this->getObjectInstancier()->get(HeliosEnvoiSAE::class)->sendArchive($transaction_id)
        );
        $this->assertLogMessage(
            "La transaction $transaction_id n'a pas pu être envoyée sur Pastell : $error_message",
            4
        );
    }

    /**
     * @throws Exception
     */
    public function testErreurEnvoiSAE()
    {
        $this->mockOpenStack();
        $this->mockPastellFactory('dsf', "", true);
        $transaction_id = $this->setTransactionEnattente();
        $this->assertFalse(
            $this->getObjectInstancier()->get(HeliosEnvoiSAE::class)->sendArchive($transaction_id)
        );
        $this->assertLogMessage(
            "La transaction $transaction_id n'a pas pu être envoyée sur Pastell : [Exception] Error Send SAE",
            4
        );
    }

    /**
     * @throws Exception
     */
    public function testErreurEnvoiSAEPuisErreurSuppression()
    {
        $this->mockOpenStack();
        $this->mockPastellFactory('dsf', "", true, true);
        $transaction_id = $this->setTransactionEnattente();
        $this->assertFalse(
            $this->getObjectInstancier()->get(HeliosEnvoiSAE::class)->sendArchive($transaction_id)
        );
        $this->assertLogMessage(
            "La transaction $transaction_id n'a pas pu être envoyée sur Pastell : [Exception] Error Send SAE et erreur lors de la suppression de dsf\[Exception] Error on delete",
            4
        );
    }
}
