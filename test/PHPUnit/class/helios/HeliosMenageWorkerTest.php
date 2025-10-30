<?php

declare(strict_types=1);

namespace PHPUnit\class\helios;

use Exception;
use HeliosUtilitiesTestTrait;
use Monolog\Level;
use S2low\Services\CloudFileStorageInterface;
use S2low\Services\LocalFileResolver;
use S2low\Services\RemoveStoredFilesOnDisk;
use S2lowLegacy\Class\helios\HeliosMenageWorker;
use S2lowLegacy\Class\TmpFolder;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use S2lowTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class HeliosMenageWorkerTest extends S2lowTestCase
{
    use HeliosUtilitiesTestTrait;

    private TmpFolder $tmpFolder;
    private string $helios_files_upload_root;
    private string $repertoirePesAllerSansTransaction;
    private HeliosMenageWorker $worker;
    private OpenStackSwiftWrapper $swift;
    private HeliosTransactionsSQL $transactionsSQL;

    public function getHeliosTransactionsSQL(): HeliosTransactionsSQL
    {
        return $this->transactionsSQL;
    }
    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpFolder = new TmpFolder();
        $this->helios_files_upload_root = $this->tmpFolder->create();
        $this->repertoirePesAllerSansTransaction = $this->tmpFolder->create();
        $this->testPesAllerPrefix = $this->helios_files_upload_root;
        $this->transactionsSQL = self::getContainer()->get(HeliosTransactionsSQL::class);
    }

    /**
     * This method is called after each test.
     */
    public function tearDown(): void
    {
        $this->tmpFolder->delete($this->helios_files_upload_root);
        $this->tmpFolder->delete($this->repertoirePesAllerSansTransaction);
        parent::tearDown();
    }


    public function testGetAllId(): void
    {
        $worker = $this->getHeliosMenageWorker(true);

        static::assertSame([1], $worker->getAllId());
    }

    /**
     * @throws \Exception
     */
    public function testWorkRecentlyCreated(): void
    {
        $pes_aller_path = $this->createPesAller();
        $worker = $this->getHeliosMenageWorker(true);
        $this->createTransaction();
        $worker->work(1);
        static::assertFileExists($pes_aller_path);
        self::assertTrue(
            $this->testHandler->hasRecord(
                'le fichier ab3321d34d3fb32b52332befa534c9854fff677b est trop recent pour etre supprimé',
                Level::Debug
            )
        );
    }

    /**
     * @throws \Exception
     */
    public function testMoveOrphelinFiles(): void
    {
        $pes_aller_path = $this->createPesAller(true);
        $worker = $this->getHeliosMenageWorker(false);
        $worker->work(1);
        static::assertFileDoesNotExist($pes_aller_path);
        static::assertFileExists(
            $this->repertoirePesAllerSansTransaction . '/ab3321d34d3fb32b52332befa534c9854fff677b'
        );

        self::assertTrue(
            $this->testHandler->hasRecord(
                'Déplacement du fichier ' . basename($pes_aller_path),
                Level::Debug
            )
        );
        self::assertTrue(
            $this->testHandler->hasRecord(
                "Pas de transaction associé au fichier " . basename($pes_aller_path),
                Level::Debug
            )
        );
    }

    /**
     * @throws \Exception
     */
    public function testWorkExistsInCloud(): void
    {
        $pes_aller_path = $this->createPesAller(true);
        $worker = $this->getHeliosMenageWorker(true);
        $this->createTransaction();
        $worker->work(1);
        static::assertFileDoesNotExist($pes_aller_path);
        static::assertFileDoesNotExist(
            $this->repertoirePesAllerSansTransaction . '/ab3321d34d3fb32b52332befa534c9854fff677b'
        );

        self::assertTrue(
            $this->testHandler->hasRecord(
                "Le fichier $pes_aller_path est supprimé",
                Level::Info
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testWorkWithTransaction(): void
    {
        $pes_aller_path = $this->createPesAller(true);
        $transaction_id = $this->createTransaction();
        $this->transactionsSQL->setTransactionInCloud($transaction_id, true);
        $this->transactionsSQL->setTransactionAvailable($transaction_id, false);
        $worker = $this->getHeliosMenageWorker(false);
        $worker->work(1);
        static::assertFileExists($pes_aller_path);
        static::assertFileDoesNotExist(
            $this->repertoirePesAllerSansTransaction . '/ab3321d34d3fb32b52332befa534c9854fff677b'
        );
    }

    private function createPesAller(bool $createOldFile = false, int $mtime = 20): string
    {
        $pes_aller_path = $this->testPesAllerPrefix . '/ab3321d34d3fb32b52332befa534c9854fff677b';
        file_put_contents($pes_aller_path, '<test></test>');
        if ($createOldFile) {
            $timestamp = strtotime("-" . $mtime . " days");

            touch($pes_aller_path, $timestamp);
        }
        return $pes_aller_path;
    }

    private function getHeliosMenageWorker(bool $fileExistsOnCloud): HeliosMenageWorker
    {
        $localPesAllerResolver = new LocalFileResolver(
            self::getContainer()->get(HeliosTransactionsSQL::class),
            $this->testPesAllerPrefix,
        );
        $storePesAller = self::createMock(CloudFileStorageInterface::class);
        $storePesAller->method('fileExistOnCloud')->willReturn($fileExistsOnCloud);

        $finder = new Finder();
        $finder->in($this->testPesAllerPrefix);

        $removePesAller = new RemoveStoredFilesOnDisk(
            $this->logger,
            self::getContainer()->get(Filesystem::class),
            $storePesAller,
            self::getContainer()->get(HeliosTransactionsSQL::class),
            $localPesAllerResolver,
            $finder,
            $this->testPesAllerPrefix,
            $this->repertoirePesAllerSansTransaction,
            true
        );

        return new HeliosMenageWorker(
            $this->logger,
            $removePesAller
        );
    }
}
