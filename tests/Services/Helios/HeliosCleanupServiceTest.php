<?php

namespace S2low\Tests\Services\Helios;

use S2low\Services\Helios\HeliosCleanupService;
use S2lowLegacy\Class\helios\PesAllerRetriever;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use Symfony\Component\Filesystem\Filesystem;

class HeliosCleanupServiceTest extends \S2lowTestCase
{
    private HeliosTransactionsSQL $heliosTransactionsSQL;
    private HeliosCleanupService $heliosCleanupService;
    private const PES_ALLER_FILE_PATH = __DIR__ . "/../fixtures/dummyPesAller.xml";
    private const PES_ACQUIT_FILE_PATH = __DIR__ . "/../fixtures/dummyPesAcquit.xml";
    private const PES_ACQUIT_FILE_NAME = 'dummyPesAcquit.xml';


    public function setUp(): void
    {
        parent::setUp();
        $this->sha1File = sha1_file(self::PES_ALLER_FILE_PATH);

        $this->heliosTransactionsSQL = self::getContainer()->get(HeliosTransactionsSQL::class);
        $this->heliosCleanupService = self::getContainer()->get(HeliosCleanupService::class);
        $this->pesAllerRetrieverService = self::getContainer()->get(PesAllerRetriever::class);
        $this->fileSystem = self::getContainer()->get(Filesystem::class);
        $this->heliosFilesUploadRoot = self::getContainer()->getParameter('app.helios_files_upload_root');
        $this->heliosResponsesRoot = self::getContainer()->getParameter('app.helios_responses_root');
    }

    public function testTransactionIsDeleted()
    {
        $transactionId = $this->createDummyTransaction();
        $this->heliosCleanupService->removeTransactionAndArtefactsAssociated($transactionId);

        $this->assertTransactionIsDeleted($transactionId);
        $this->assertFilesAreNotOnDisk();
//        $this->assertFilesAreNotOnCloud($transactionId);
    }

    private function assertTransactionIsDeleted(int $transactionId): void
    {
        self::assertFalse($this->heliosTransactionsSQL->getInfo($transactionId));
    }

    private function createDummyTransaction(): int
    {
        $pesAllerNewEmplacement = $this->pesAllerRetrieverService->getPathForNonExistingFile($this->sha1File);
        copy(self::PES_ALLER_FILE_PATH, $pesAllerNewEmplacement);
        $transactionId = $this->heliosTransactionsSQL->create('dummyPesAller.xml', $this->sha1File, 13, 1, 0, 1234567890);

        copy(self::PES_ACQUIT_FILE_PATH, $this->heliosResponsesRoot . "/" . self::PES_ACQUIT_FILE_NAME);
        $this->heliosTransactionsSQL->setAcquitFilename($transactionId, self::PES_ACQUIT_FILE_NAME);

        return $transactionId;
    }

    private function assertPESAllerIsDeleted(): void
    {
        self::assertFalse($this->fileSystem->exists($this->heliosFilesUploadRoot . "/" . $this->sha1File));
    }

    private function assertPESAcquitIsDeleted(): void
    {
        self::assertFalse($this->fileSystem->exists($this->heliosResponsesRoot . "/" . self::PES_ACQUIT_FILE_NAME));
    }

    private function assertFilesAreNotOnDisk(): void
    {
        $this->assertPESAllerIsDeleted();
        $this->assertPESAcquitIsDeleted();
    }

    private function assertFilesAreNotOnCloud(int $transactionId)
    {
    }
}
