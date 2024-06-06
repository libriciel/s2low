<?php

declare(strict_types=1);

namespace PHPUnit\class\helios;

use Exception;
use HeliosUtilitiesTestTrait;
use S2lowLegacy\Class\CloudStorageFactory;
use S2lowLegacy\Class\helios\PESAllerCloudStorage;
use S2lowLegacy\Class\TmpFolder;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use S2lowTestCase;

class PesAllerStorageTest extends S2lowTestCase
{
    use HeliosUtilitiesTestTrait;

    private const SHA1_EXEMPLE = 'ab3321d34d3fb32b52332befa534c9854fff677b';

    /**
     * @param bool $fileExistsOnCloud
     * @return string
     * @throws Exception
     */
    private function mockOpenStackSwiftWrapper(bool $fileExistsOnCloud): string
    {

        $tmpFolder = new TmpFolder();
        $tmp_folder = $tmpFolder->create();
        $pes_aller_path = $tmp_folder . '/' . self::SHA1_EXEMPLE;
        file_put_contents($pes_aller_path, '<test></test>');

        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackSwiftWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $openStackSwiftWrapper
            ->method('fileExistsOnCloud')
            ->willReturn($fileExistsOnCloud);
        $this->getObjectInstancier()->set(OpenStackSwiftWrapper::class, $openStackSwiftWrapper);
        $this->getObjectInstancier()->set('helios_files_upload_root', $tmp_folder);
        return $pes_aller_path;
    }

    /**
     * @throws Exception
     */
    public function testDelete()
    {
        $pes_aller_path = $this->mockOpenStackSwiftWrapper(true);
        $transaction_id = $this->createTransaction();
        static::assertFileExists($pes_aller_path);
        $this->getObjectInstancier()
            ->get(CloudStorageFactory::class)
            ->getInstanceByClassName(PESAllerCloudStorage::class)
            ->deleteIfIsInCloud($transaction_id);
        static::assertFileDoesNotExist($pes_aller_path);
    }

    /**
     * @throws Exception
     */
    public function testDeleteNotOnCloud()
    {
        $pes_aller_path = $this->mockOpenStackSwiftWrapper(false);
        $transaction_id = $this->createTransaction();
        static::assertFileExists($pes_aller_path);
        $this->getObjectInstancier()
            ->get(CloudStorageFactory::class)
            ->getInstanceByClassName(PESAllerCloudStorage::class)
            ->deleteIfIsInCloud($transaction_id);
        static::assertFileExists($pes_aller_path);
    }


    /**
     * @throws Exception
     */
    public function testStoreNotAvailable()
    {

        $this->getObjectInstancier()->set('repertoirePesAllerSansTransaction', '');
        $transaction_id = $this->createTransaction();
        $heliosTransactionsSQL = $this->getObjectInstancier()->get(HeliosTransactionsSQL::class);

        /** @var \S2lowLegacy\Class\CloudStorage $cloudStorage */
        $cloudStorage = $this->getObjectInstancier()
            ->get(CloudStorageFactory::class)
            ->getInstanceByClassName(PESAllerCloudStorage::class);
        $cloudStorage->storeObject($transaction_id);

        $transaction_info = $heliosTransactionsSQL->getInfo($transaction_id);
        static::assertTrue($transaction_info['not_available']);
    }

    /**
     * @throws \S2lowLegacy\Lib\PausingQueueException
     * @throws \S2lowLegacy\Lib\UnrecoverableException
     * @throws \S2lowLegacy\Class\CloudStorageException
     */
    public function testStoreSuccess()
    {
        $transaction_id = $this->createTransaction();
        $heliosTransactionsSQL = $this->getObjectInstancier()->get(HeliosTransactionsSQL::class);

        $transaction_info = $heliosTransactionsSQL->getInfo($transaction_id);

        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackSwiftWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $openStackSwiftWrapper->method('sendFile')->willReturn(true);
        $openStackSwiftWrapper->method('fileExistsOnCloud')->willReturn(true);

        $this->getObjectInstancier()->set(OpenStackSwiftWrapper::class, $openStackSwiftWrapper);

        $helios_files_upload_root = $this->getObjectInstancier()->get('helios_files_upload_root');
        file_put_contents($helios_files_upload_root . '/' . $transaction_info['sha1'], 'test');

        /** @var \S2lowLegacy\Class\CloudStorage $cloudStorage */
        $cloudStorage = $this->getObjectInstancier()
            ->get(CloudStorageFactory::class)
            ->getInstanceByClassName(PESAllerCloudStorage::class);
        $cloudStorage->storeObject($transaction_id);

        static::assertTrue($cloudStorage->storeObject($transaction_id));

        unlink($helios_files_upload_root . '/' . $transaction_info['sha1']);
    }

    /**
     * @throws \S2lowLegacy\Lib\PausingQueueException
     * @throws \S2lowLegacy\Lib\UnrecoverableException
     * @throws \S2lowLegacy\Class\CloudStorageException
     */
    public function testStoreFailure()
    {
        $transaction_id = $this->createTransaction();
        $heliosTransactionsSQL = $this->getObjectInstancier()->get(HeliosTransactionsSQL::class);

        $transaction_info = $heliosTransactionsSQL->getInfo($transaction_id);

        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackSwiftWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $openStackSwiftWrapper->method('sendFile')->willReturn(false);

        $this->getObjectInstancier()->set(OpenStackSwiftWrapper::class, $openStackSwiftWrapper);

        $helios_files_upload_root = $this->getObjectInstancier()->get('helios_files_upload_root');
        file_put_contents($helios_files_upload_root . '/' . $transaction_info['sha1'], 'test');

        /** @var \S2lowLegacy\Class\CloudStorage $cloudStorage */
        $cloudStorage = $this->getObjectInstancier()
            ->get(CloudStorageFactory::class)
            ->getInstanceByClassName(PESAllerCloudStorage::class);
        $cloudStorage->storeObject($transaction_id);

        static::assertFalse($cloudStorage->storeObject($transaction_id));

        unlink($helios_files_upload_root . '/' . $transaction_info['sha1']);
    }
}
