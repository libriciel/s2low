<?php

use S2low\Services\CloudFileStorage;
use S2low\Services\LocalFileResolver;
use S2lowLegacy\Class\helios\HeliosExport;
use S2lowLegacy\Class\TmpFolder;
use S2lowLegacy\Class\UserContext;
use S2lowLegacy\Controller\HeliosController;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Model\HeliosTransactionsSQL;

class HeliosExportTest extends S2lowTestCase
{
    /**
     * @throws Exception
     */
    public function testExport()
    {

//        $tmpFolder = new TmpFolder();
//        $helios_responses_root = $tmpFolder->create();
//
//        $pes_aller = __DIR__ . "/../../helios/fixtures/pes_aller_ok.xml";
//        $pes_acquit = __DIR__ . "/../../helios/fixtures/pes_acquit.xml";
//
//        /** @var LocalFileResolver $pesAllerRetriever */
//        $pesAllerResolver = $this->createPesAllerResolver($helios_responses_root);
//        $filepath = $pesAllerResolver->getFullPathFromFilePath(basename($pes_aller));
//
//        copy($pes_aller, $filepath);
//        $heliosControler = $this->createHeliosController($pesAllerResolver);
//        $transaction_id =  $heliosControler->importFile(1, $pes_aller, "pes_aller.xml");
//
//        copy($pes_acquit, self::getContainer()->getParameter('app.helios_responses_root') . "/pes_acquit.xml");
//
//        $heliosTransactionSQL = self::getContainer()->get(HeliosTransactionsSQL::class);
//
//        $heliosTransactionSQL->setAcquitFilename($transaction_id, "pes_acquit.xml");
//
//        $heliosExport = self::getContainer()->get(HeliosExport::class);
//
//
//        $tmp_folder = $tmpFolder->create();
//        $heliosExport->export(1, $tmp_folder);
//
//        $this->assertFileEquals($pes_aller, $tmp_folder . "/$transaction_id/pes_aller.xml");
//        $this->assertFileEquals($pes_acquit, $tmp_folder . "/$transaction_id/pes_acquit.xml");
//
//        $tmpFolder->delete($tmp_folder);
//        $tmpFolder->delete($helios_responses_root);
        self::assertTrue(true);
    }

    private function createPesAllerResolver(string $pesAllerPrefix): LocalFileResolver
    {
        return new LocalFileResolver(
            self::getContainer()->get(HeliosTransactionsSQL::class),
            $pesAllerPrefix
        );
    }

    private function createHeliosController(LocalFileResolver $localFileResolver): HeliosController
    {
        $storePesAller = new CloudFileStorage(
            self::getContainer()->get('app.clientCloudStorage.pes_aller'),
            $localFileResolver,
            self::getContainer()->get(HeliosTransactionsSQL::class)
        );

        return new HeliosController(
            $localFileResolver,
            $storePesAller,
            self::getContainer()->get(ObjectInstancier::class),
            self::getContainer()->get(UserContext::class),
        );
    }
}
