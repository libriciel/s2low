<?php

use PHPUnit\ActesUtilitiesTestTrait;
use S2lowLegacy\Class\actes\ActesEnvelopeSQL;
use S2lowLegacy\Class\actes\ActesIncludedFileSQL;
use S2lowLegacy\Class\actes\ActesRetriever;
use S2lowLegacy\Class\actes\ActesSignature;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\ActesWorkspace;
use S2lowLegacy\Class\ActesWorkspaceForTests;
use S2lowLegacy\Class\S2lowLogger;
use S2lowLegacy\Class\TGZExtractor;
use S2lowLegacy\Class\TmpFolder;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;

class ActesSignaturesTest extends S2lowTestCase
{
    use ActesUtilitiesTestTrait;

    private ActesRetriever $actesRetriever;

    public function setUp(): void
    {
        parent::setUp();
        $this->workspace = new ActesWorkspaceForTests();

        $this->actesRetriever = new ActesRetriever(
            $this->getObjectInstancier()->get(OpenStackSwiftWrapper::class),
            $this->getObjectInstancier()->get(S2lowLogger::class),
            $this->workspace
        );
    }

    public function tearDown(): void
    {
        $this->workspace->clear();
    }
    /**
     * @throws Exception
     */
    public function testSetSignature()
    {
        $tmpFolder = new TmpFolder();

        $tmp_dir = $this->workspace->getFilesUploadRoot();
        $actesCreator = $this->getObjectInstancier()->get(ActesCreator::class);

        $transaction_id = $actesCreator->createTransaction(ActesStatusSQL::STATUS_EN_ATTENTE_D_ETRE_SIGNEE, __DIR__ . "/fixtures/abc-TACT--000000000--20170803-16.tar.gz", $tmp_dir);

        $actesTransactionSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
        $transction_info = $actesTransactionSQL->getInfo($transaction_id);

        $actesIncludedFileSQL = $this->getObjectInstancier()->get(ActesIncludedFileSQL::class);
        $included_file_id = $actesIncludedFileSQL->addIncludedFile($transction_info['envelope_id'], $transaction_id, 'application/pdf', 42, '034-000000000-20170801-20170803E-AI-1-1_0.xml');


        $actesSignature = new ActesSignature(
            $this->getObjectInstancier()->get(ActesIncludedFileSQL::class),
            $this->getObjectInstancier()->get(ActesTransactionsSQL::class),
            $this->getObjectInstancier()->get(ActesEnvelopeSQL::class),
            $this->actesRetriever
        );
        $actesSignature->setSignature($included_file_id, "ma signature");

        $actes_envelope_info = $this->getObjectInstancier()->get(ActesEnvelopeSQL::class)->getInfo($transction_info['envelope_id']);


        $archivePath = $this->workspace->getFilesUploadRoot() . '/' . $actes_envelope_info['file_path'];

        $result_dir = $tmpFolder->create();
        $tgzExtractor = new TGZExtractor($result_dir);
        $tgzExtractor->extract($archivePath, false);

        $this->assertFileEquals(
            __DIR__ . "/fixtures/fichier-metier-signe.xml",
            $result_dir . "/034-000000000-20170801-20170803E-AI-1-1_0.xml"
        );

        $tmpFolder->delete($result_dir);
    }

    public function getActesTransactionsSQL(): ActesTransactionsSQL
    {
        return $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
    }

    public function getActesWorkspace(): ActesWorkspaceForTests
    {
        // TODO: Implement getWorkspace() method.
    }
}
