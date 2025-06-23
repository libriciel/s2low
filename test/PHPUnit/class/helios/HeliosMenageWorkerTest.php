<?php

declare(strict_types=1);

namespace PHPUnit\class\helios;

use Exception;
use HeliosUtilitiesTestTrait;
use Monolog\Level;
use S2lowLegacy\Class\helios\HeliosMenageWorker;
use S2lowLegacy\Class\helios\PESAllerCloudStorable;
use S2lowLegacy\Class\helios\PESAllerCloudStorage;
use S2lowLegacy\Class\TmpFolder;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use S2lowTestCase;

class HeliosMenageWorkerTest extends S2lowTestCase
{
    use HeliosUtilitiesTestTrait;

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
        $this->swift = $this->getMockBuilder(OpenStackSwiftWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->worker = $this->getHeliosMenageWorker();

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
        static::assertSame([1], $this->worker->getAllId());
    }

    /**
     * @throws \Exception
     */
    public function testWorkRecentylCreated(): void
    {
        $pes_aller_path = $this->createPesAller();
        $this->swift->expects(self::never())->method('fileExistsOnCloud');
        $this->worker->work(1);
        static::assertFileExists($pes_aller_path);
        self::assertTrue(
            $this->testHandler->hasRecord(
                'File ab3321d34d3fb32b52332befa534c9854fff677b too young to die : not deleted',
                Level::Debug
            )
        );
    }

    /**
     * @throws \Exception
     */
    public function testWorkWithoutTransactionId(): void
    {
        $pes_aller_path = $this->createPesAller(true);
        $this->swift->expects(self::atLeastOnce())->method('fileExistsOnCloud')->willReturn(false);
        $this->worker->work(1);
        static::assertFileDoesNotExist($pes_aller_path);
        static::assertFileExists(
            $this->repertoirePesAllerSansTransaction . '/ab3321d34d3fb32b52332befa534c9854fff677b'
        );

        self::assertTrue(
            $this->testHandler->hasRecord(
                'File ' . $pes_aller_path . ' not existing on cloud : not deleted',
                Level::Info
            )
        );
        self::assertTrue(
            $this->testHandler->hasRecord(
                "Unable to find object id for the file $pes_aller_path",
                Level::Notice
            )
        );
    }

    /**
     * @throws \Exception
     */
    public function testWorkExistsInCloud(): void
    {
        $pes_aller_path = $this->createPesAller(true);
        $this->swift->expects(self::atLeastOnce())->method('fileExistsOnCloud')->willReturn(true);
        $this->worker->work(1);
        static::assertFileDoesNotExist($pes_aller_path);
        static::assertFileDoesNotExist(
            $this->repertoirePesAllerSansTransaction . '/ab3321d34d3fb32b52332befa534c9854fff677b'
        );

        self::assertTrue(
            $this->testHandler->hasRecord(
                "Deleting file : $pes_aller_path",
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
        $this->swift->expects(self::atLeastOnce())->method('fileExistsOnCloud')->willReturn(false);
        $transaction_id = $this->createTransaction();
        $this->transactionsSQL->setTransactionInCloud($transaction_id, true);
        $this->transactionsSQL->setTransactionAvailable($transaction_id, false);
        $this->worker->work(1);
        static::assertFileExists($pes_aller_path);
        static::assertFileDoesNotExist(
            $this->repertoirePesAllerSansTransaction . '/ab3321d34d3fb32b52332befa534c9854fff677b'
        );
        static::assertFalse($this->transactionsSQL->isTransactionInCloud($transaction_id));
        static::assertTrue($this->transactionsSQL->isTransactionAvailable($transaction_id));
    }

    private function createPesAller(bool $createOldFile = false): string
    {
        $pes_aller_path = $this->helios_files_upload_root . '/ab3321d34d3fb32b52332befa534c9854fff677b';
        file_put_contents($pes_aller_path, '<test></test>');
        if ($createOldFile) {
            touch($pes_aller_path, 0);
        }
        return $pes_aller_path;
    }

    private function getHeliosMenageWorker(): HeliosMenageWorker
    {
        return new HeliosMenageWorker(
            $this->getPesAllerCloudStorage()
        );
    }

    private function getPesAllerCloudStorage(): PESAllerCloudStorage
    {
        $pesAllerCloudStorable = new PesAllerCloudStorable(
            $this->helios_files_upload_root,
            self::getContainer()->get(HeliosTransactionsSQL::class),
            $this->repertoirePesAllerSansTransaction,
        );

        return new PesAllerCloudStorage(
            $pesAllerCloudStorable,
            $this->swift,
            $this->logger,
            openstack_enable: false
        );
    }
}
