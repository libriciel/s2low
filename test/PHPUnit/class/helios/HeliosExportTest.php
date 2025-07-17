<?php

use S2lowLegacy\Class\helios\HeliosExport;
use S2lowLegacy\Class\helios\PesAllerRetriever;
use S2lowLegacy\Class\TmpFolder;
use S2lowLegacy\Controller\HeliosController;
use S2lowLegacy\Model\HeliosTransactionsSQL;

class HeliosExportTest extends S2lowTestCase
{
    /**
     * @throws Exception
     */
    public function testExport()
    {

        $tmpFolder = new TmpFolder();
        $helios_responses_root = $tmpFolder->create();

        $pes_aller = __DIR__ . "/../../helios/fixtures/pes_aller_ok.xml";
        $pes_acquit = __DIR__ . "/../../helios/fixtures/pes_acquit.xml";

        /** @var PesAllerRetriever $pesAllerRetriever */
        $pesAllerRetriever = self::getContainer()->get(PesAllerRetriever::class);
        $filepath = $pesAllerRetriever->getPathForNonExistingFile(sha1_file($pes_aller));

        copy($pes_aller, $filepath);
        $heliosControler = self::getContainer()->get(HeliosController::class);
        $transaction_id =  $heliosControler->importFile(1, $pes_aller, "pes_aller.xml");

        copy($pes_acquit, self::getContainer()->getParameter('app.helios_responses_root') . "/pes_acquit.xml");

        $heliosTransactionSQL = self::getContainer()->get(HeliosTransactionsSQL::class);

        $heliosTransactionSQL->setAcquitFilename($transaction_id, "pes_acquit.xml");

        $heliosExport = self::getContainer()->get(HeliosExport::class);


        $tmp_folder = $tmpFolder->create();
        $heliosExport->export(1, $tmp_folder);

        $this->assertFileEquals($pes_aller, $tmp_folder . "/$transaction_id/pes_aller.xml");
        $this->assertFileEquals($pes_acquit, $tmp_folder . "/$transaction_id/pes_acquit.xml");

        $tmpFolder->delete($tmp_folder);
        $tmpFolder->delete($helios_responses_root);
    }
}
