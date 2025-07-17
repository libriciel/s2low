<?php

declare(strict_types=1);

namespace PHPUnit\class\helios;

use Exception;
use HeliosUtilitiesTestTrait;
use S2lowLegacy\Class\helios\PESAllerCloudStorable;
use S2lowLegacy\Class\helios\PESAllerCloudStorage;
use S2lowLegacy\Class\TmpFolder;
use S2lowLegacy\Lib\OpenStackContainerStore;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use S2lowTestCase;

class PesAllerStorageTest extends S2lowTestCase
{
    use HeliosUtilitiesTestTrait;

    private const SHA1_EXEMPLE = 'ab3321d34d3fb32b52332befa534c9854fff677b';
    private string $pes_aller_path = '';

    public function setUp(): void
    {
        parent::setUp();
        $this->tmpFolder = new TmpFolder();
        $this->helios_files_upload_root = $this->tmpFolder->create();
        $this->repertoirePesAllerSansTransaction = $this->helios_files_upload_root;
    }

    public function tearDown(): void
    {
        $this->tmpFolder->delete($this->helios_files_upload_root);
        if (file_exists($this->pes_aller_path)) {
            unlink($this->pes_aller_path);
        }
        parent::tearDown();
    }

    public function getHeliosTransactionsSQL(): HeliosTransactionsSQL
    {
        return $this->getObjectInstancier()->get(HeliosTransactionsSQL::class);
    }

    private function mockOpenStackSwiftWrapper(bool $fileExistsOnCloud): \PHPUnit\Framework\MockObject\MockObject
    {
        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackSwiftWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $openStackSwiftWrapper
            ->method('fileExistsOnCloud')
            ->willReturn($fileExistsOnCloud);

        return $openStackSwiftWrapper;
    }

    /**
     * @throws Exception
     */
    public function testDelete()
    {
        $this->pes_aller_path = $this->helios_files_upload_root . '/' . self::SHA1_EXEMPLE;
        file_put_contents($this->pes_aller_path, '<test></test>');

        $openStackSwiftWrapper = $this->mockOpenStackSwiftWrapper(true);
        $transaction_id = $this->createTransaction();
        static::assertFileExists($this->pes_aller_path);
        $pesAllerCloudStorage = $this->getPesAllerCloudStorage($openStackSwiftWrapper);
        $pesAllerCloudStorage->deleteIfIsInCloud($transaction_id);
        static::assertFileDoesNotExist($this->pes_aller_path);
    }

    /**
     * @throws Exception
     */
    public function testDeleteNotOnCloud()
    {
        $this->pes_aller_path = $this->helios_files_upload_root . '/' . self::SHA1_EXEMPLE;
        file_put_contents($this->pes_aller_path, '<test></test>');

        $openStackSwiftWrapper = $this->mockOpenStackSwiftWrapper(false);
        $transaction_id = $this->createTransaction();
        static::assertFileExists($this->pes_aller_path);
        $pesAllerCloudStorage = $this->getPesAllerCloudStorage($openStackSwiftWrapper);
        $pesAllerCloudStorage->deleteIfIsInCloud($transaction_id);
        static::assertFileExists($this->pes_aller_path);
    }

    public function testStoreNotAvailable()
    {
        self::assertTrue(true);
        $this->repertoirePesAllerSansTransaction = '';
        $openStackSwiftWrapper = $this->mockOpenStackSwiftWrapper(false);

        $transaction_id = $this->createTransaction();
        $heliosTransactionsSQL = self::getContainer()->get(HeliosTransactionsSQL::class);
        $cloudStorage = $this->getPesAllerCloudStorage($openStackSwiftWrapper);
        $cloudStorage->storeObject($transaction_id);
        $transaction_info = $heliosTransactionsSQL->getInfo($transaction_id);

        static::assertTrue($transaction_info['not_available']);
    }

    public function testStoreSuccess()
    {
        $transaction_id = $this->createTransaction();
        $heliosTransactionsSQL = self::getContainer()->get(HeliosTransactionsSQL::class);

        $transaction_info = $heliosTransactionsSQL->getInfo($transaction_id);

        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackSwiftWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $openStackSwiftWrapper->method('sendFile')->willReturn(true);
        $openStackSwiftWrapper->method('fileExistsOnCloud')->willReturn(true);


        file_put_contents($this->helios_files_upload_root . '/' . $transaction_info['sha1'], 'test');

        $cloudStorage = $this->getPesAllerCloudStorage($openStackSwiftWrapper);
        $cloudStorage->storeObject($transaction_id);

        static::assertTrue($cloudStorage->storeObject($transaction_id));

        unlink($this->helios_files_upload_root . '/' . $transaction_info['sha1']);
    }

    public function testStoreFailure()
    {
        $transaction_id = $this->createTransaction();
        $heliosTransactionsSQL = self::getContainer()->get(HeliosTransactionsSQL::class);

        $transaction_info = $heliosTransactionsSQL->getInfo($transaction_id);

        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackSwiftWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $openStackSwiftWrapper->method('sendFile')->willReturn(false);


        file_put_contents($this->helios_files_upload_root . '/' . $transaction_info['sha1'], 'test');

        $cloudStorage = $this->getPesAllerCloudStorage($openStackSwiftWrapper);
        $cloudStorage->storeObject($transaction_id);

        static::assertFalse($cloudStorage->storeObject($transaction_id));

        unlink($this->helios_files_upload_root . '/' . $transaction_info['sha1']);
    }

    private function getPesAllerCloudStorage($openStackSwiftWrapper): PESAllerCloudStorage
    {
        $pesAllerCloudStorable = new PesAllerCloudStorable(
            $this->helios_files_upload_root,
            self::getContainer()->get(HeliosTransactionsSQL::class),
            $this->repertoirePesAllerSansTransaction,
        );

        return new PesAllerCloudStorage(
            $pesAllerCloudStorable,
            $openStackSwiftWrapper,
            $this->logger,
            openstack_enable: false
        );
    }
}
