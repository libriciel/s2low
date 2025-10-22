<?php

use Monolog\Level;
use PHPUnit\ActesUtilitiesTestTrait;
use S2lowLegacy\Class\actes\ActesExport;
use S2lowLegacy\Class\actes\ActesIncludedFileSQL;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\actes\ActeTamponne;
use S2lowLegacy\Class\TmpFolder;
use S2lowLegacy\Lib\UnrecoverableException;
use S2lowLegacy\Model\AuthoritySQL;

class ActesExportTest extends S2lowTestCase
{
    use ActesUtilitiesTestTrait;

    private const ENVELOPPE_TEST_PATH = __DIR__ . "/fixtures/abc-TACT--000000000--20170803-16.tar.gz";
    private const XML_TEST = "<test></test>";

    private function getActesExport(): ActesExport
    {
        $acteTamponne = $this->getMockBuilder(ActeTamponne::class)->disableOriginalConstructor()->getMock();

        return new ActesExport(
            self::getContainer()->get('app.localFileResolver.acte_enveloppe'),
            self::getContainer()->get('app.store.file.acte_enveloppe'),
            $this->s2lowLogger,
            self::getContainer()->get(AuthoritySQL::class),
            self::getContainer()->get(ActesTransactionsSQL::class),
            self::getContainer()->get(ActesIncludedFileSQL::class),
            $acteTamponne,
        );
    }

    /**
     * @throws Exception
     */
    public function testExport()
    {
        $transaction_id = $this->createTransaction(
            ActesStatusSQL::STATUS_ACQUITTEMENT_RECU,
            self::ENVELOPPE_TEST_PATH
        );

        $sql = "UPDATE actes_transactions_workflow SET flux_retour=? WHERE transaction_id=? AND status_id=?";
        $this->getSQLQuery()->query(
            $sql,
            self::XML_TEST,
            $transaction_id,
            ActesStatusSQL::STATUS_ACQUITTEMENT_RECU
        );

        $actesTransactionsSQL = self::getContainer()->get(ActesTransactionsSQL::class);

        $transaction_info = $actesTransactionsSQL->getInfo($transaction_id);

        $actesIncludedFileSQL = self::getContainer()->get(ActesIncludedFileSQL::class);

        $actesIncludedFileSQL->addIncludedFile(
            $transaction_info['envelope_id'],
            $transaction_id,
            "text/xml",
            12,
            "034-000000000-20170801-20170803E-AI-1-1_0.xml"
        );
        $actesIncludedFileSQL->addIncludedFile(
            $transaction_info['envelope_id'],
            $transaction_id,
            "application/pdf",
            12,
            "034-000000000-20170801-20170803E-AI-1-1_1.pdf"
        );


        $actesExport = $this->getActesExport();

        $tmpFolder = new TmpFolder();
        $tmp_folder = $tmpFolder->create();

        $actesExport->export(1, $tmp_folder);

        $this->assertFileExists(
            $tmp_folder . "/$transaction_id/034-000000000-20170801-20170803E-AI-1-1_0.xml"
        );
        $this->assertFileExists(
            $tmp_folder . "/$transaction_id/034-000000000-20170801-20170803E-AI-1-1_1.pdf"
        );

        $tmpFolder->delete($tmp_folder);

        $this->assertTrue(
            $this->testHandler->hasRecord(
                "[COPIE OK] 034-000000000-20170801-20170803E-AI-1-1_0.xml -> $tmp_folder/$transaction_id/034-000000000-20170801-20170803E-AI-1-1_0.xml",
                Level::Debug
            )
        );
        $this->assertTrue(
            $this->testHandler->hasRecord(
                "[DUMP OK] $tmp_folder/$transaction_id/ACK_{$transaction_id}.xml",
                Level::Debug
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testExportWhenAuthorityDoesNotExists()
    {
        $actesExport = $this->getActesExport();
        $this->setExpectedException(UnrecoverableException::class, "La collectivité 42 n'existe pas");
        $actesExport->export(42, "/tmp/");
    }

    /**
     * @throws Exception
     */
    public function testExportWhenOutputDirectoryDoesNotExists()
    {
        $actesExport = $this->getActesExport();
        $this->setExpectedException(
            UnrecoverableException::class,
            "Le répertoire /42/ n'existe pas ou n'est pas accessible en écriture"
        );
        $actesExport->export(1, "/42/");
    }

    /**
     * @throws Exception
     */
    public function testExportWhenThereIsNoTransactions()
    {
        $actesExport = $this->getActesExport();
        $actesExport->export(1, "/tmp/", 0, 0);
        $this->assertTrue(
            $this->testHandler->hasRecord(
                "Aucune transaction ne correspond aux critères",
                Level::Info
            )
        );
    }

    public function getActesTransactionsSQL(): ActesTransactionsSQL
    {
        return self::getContainer()->get(ActesTransactionsSQL::class);
    }
}
