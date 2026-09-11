<?php

use S2lowLegacy\Class\helios\HeliosExport;
use S2lowLegacy\Class\helios\PESAcquitCloudStorage;
use S2lowLegacy\Class\helios\PESAllerCloudStorage;
use S2lowLegacy\Class\helios\PesAllerRetriever;
use S2lowLegacy\Class\TmpFolder;
use S2lowLegacy\Controller\HeliosController;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\HeliosTransactionsSQL;

class HeliosExportTest extends S2lowTestCase
{
    private const AUTHORITY_ID = 1;
    private const PES_ALLER = __DIR__ . "/../../helios/fixtures/pes_aller_ok.xml";
    private const PES_ACQUIT = __DIR__ . "/../../helios/fixtures/pes_acquit.xml";

    /**
     * @throws Exception
     */
    public function testExport()
    {
        $transaction_id = $this->createTransactionAvecAcquit();

        $tmpFolder = new TmpFolder();
        $output_directory = $tmpFolder->create();

        $this->getHeliosExport()->export(self::AUTHORITY_ID, $output_directory);

        $this->assertFileEquals(self::PES_ALLER, "$output_directory/$transaction_id/pes_aller.xml");
        $this->assertFileEquals(self::PES_ACQUIT, "$output_directory/$transaction_id/pes_acquit.xml");

        $tmpFolder->delete($output_directory);
    }

    /**
     * @throws Exception
     */
    public function testExportContinueQuandUnFichierEstIntrouvable()
    {
        $transaction_id = $this->createTransactionAvecAcquit();
        unlink($this->getPesAllerPath());

        $tmpFolder = new TmpFolder();
        $output_directory = $tmpFolder->create();

        $this->getHeliosExport()->export(self::AUTHORITY_ID, $output_directory);

        $this->assertFileDoesNotExist("$output_directory/$transaction_id/pes_aller.xml");
        $this->assertFileEquals(self::PES_ACQUIT, "$output_directory/$transaction_id/pes_acquit.xml");
        $this->assertTrue(
            $this->testHandler->hasWarningThatContains(
                "[COPIE KO] transaction #ID $transaction_id : pes_aller.xml n'a pas pu être exporté"
            )
        );

        $tmpFolder->delete($output_directory);
    }

    private function createTransactionAvecAcquit(): int
    {
        copy(self::PES_ALLER, $this->getPesAllerPath());
        copy(self::PES_ACQUIT, self::getContainer()->getParameter('app.helios_responses_root') . "/pes_acquit.xml");

        $transaction_id = self::getContainer()->get(HeliosController::class)
            ->importFile(self::AUTHORITY_ID, self::PES_ALLER, "pes_aller.xml");

        self::getContainer()->get(HeliosTransactionsSQL::class)
            ->setAcquitFilename($transaction_id, "pes_acquit.xml");

        return $transaction_id;
    }

    private function getPesAllerPath(): string
    {
        /** @var PesAllerRetriever $pesAllerRetriever */
        $pesAllerRetriever = self::getContainer()->get(PesAllerRetriever::class);
        return $pesAllerRetriever->getPathForNonExistingFile(sha1_file(self::PES_ALLER));
    }

    private function getHeliosExport(): HeliosExport
    {
        return new HeliosExport(
            $this->logger,
            self::getContainer()->get(AuthoritySQL::class),
            self::getContainer()->get(HeliosTransactionsSQL::class),
            self::getContainer()->get(PESAllerCloudStorage::class),
            self::getContainer()->get(PESAcquitCloudStorage::class)
        );
    }
}
