<?php

declare(strict_types=1);

namespace PHPUnit\class\actes;

use Exception;
use Monolog\Handler\TestHandler;
use S2lowLegacy\Class\actes\ActesCloudStorage;
use S2lowLegacy\Class\actes\ActesEnvelopeSQL;
use S2lowLegacy\Class\actes\ActesEnvelopeStorage;
use S2lowLegacy\Class\ActesWorkspace;
use S2lowLegacy\Class\CloudStorageFactory;
use S2lowLegacy\Lib\OpenStackContainerStore;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;
use PHPUnit\Framework\MockObject\MockObject;
use S2lowTestCase;

class ActesEnvelopeStorageTest extends S2lowTestCase
{
    private const S2LOW_PHPUNIT_ACTE_ENVELOPE_STORAGE_TEST = 's2low-phpunit-acte-envelope-storage-test';
    private const MIN_DATE = '1970-01-01';
    private const MESSAGE = 'message';
    private string $dateTomorrow;
    private ActesWorkspace $workspace;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dateTomorrow = date('Y-m-d', strtotime('tomorrow'));

        $openStackContainersManager =
            $this->getMockBuilder(OpenStackContainerStore::class)
                ->disableOriginalConstructor()
                ->getMock();

        $openStackContainersManager->expects(static::never())
            ->method(static::anything());

        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackSwiftWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $openStackSwiftWrapper
            ->expects(static::never())
            ->method('retrieveFile');

        $openStackSwiftWrapper
            ->expects(static::never())
            ->method('deleteFile');

        $openStackSwiftWrapper
            ->method('fileExistsOnCloud')
            ->willReturn(true);

        $this->getObjectInstancier()->set(OpenStackSwiftWrapper::class, $openStackSwiftWrapper);

        $this->workspace = $this->actesWorkspaceManager->get();
    }


    public function testGrandMenage()
    {
        $actesEnvelopeSQL = $this->getObjectInstancier()->get(ActesEnvelopeSQL::class);

        $filename = self::S2LOW_PHPUNIT_ACTE_ENVELOPE_STORAGE_TEST . mt_rand(0, mt_getrandmax());

        $actes_files_upload_root =  $this->workspace->getFilesUploadRoot();
        file_put_contents("$actes_files_upload_root/$filename", 'foo');

        $transaction_id = $actesEnvelopeSQL->create(1, $filename);
        $actesEnvelopeSQL->setTransactionInCloud($transaction_id);
        $this->getObjectInstancier()->set(ActesWorkspace::class, $this->workspace);
        $actesEnvelopeStorage = $this->getObjectInstancier()->get(ActesEnvelopeStorage::class);
        $actesEnvelopeStorage->grandMenage(self::MIN_DATE, $this->dateTomorrow, 'ok');
        $testHandler = $this->getObjectInstancier()->get(TestHandler::class);
        static::assertFalse(file_exists($actes_files_upload_root . "/$filename"));
        static::assertSame("File $filename deleted", $testHandler->getRecords()[3][self::MESSAGE]);
    }

    public function testGrandMenageFileNotExists()
    {
        $actesEnvelopeSQL = $this->getObjectInstancier()->get(ActesEnvelopeSQL::class);
        $filename = self::S2LOW_PHPUNIT_ACTE_ENVELOPE_STORAGE_TEST . mt_rand(0, mt_getrandmax());

        $transaction_id = $actesEnvelopeSQL->create(1, $filename);
        $actesEnvelopeSQL->setTransactionInCloud($transaction_id);
        $this->getObjectInstancier()->set(ActesWorkspace::class, $this->workspace);
        $actesEnvelopeStorage = $this->getObjectInstancier()->get(ActesEnvelopeStorage::class);
        $actesEnvelopeStorage->grandMenage(self::MIN_DATE, $this->dateTomorrow, true);
        $testHandler = $this->getObjectInstancier()->get(TestHandler::class);
        static::assertSame("File not exists $filename [PASS]", $testHandler->getRecords()[2][self::MESSAGE]);
    }

    public function testGrandMenageNotConfirm()
    {
        $actesEnvelopeSQL = $this->getObjectInstancier()->get(ActesEnvelopeSQL::class);

        $filename = self::S2LOW_PHPUNIT_ACTE_ENVELOPE_STORAGE_TEST . mt_rand(0, mt_getrandmax());

        $actes_files_upload_root =  $this->workspace->getFilesUploadRoot();
        file_put_contents($actes_files_upload_root . "/$filename", 'foo');

        $transaction_id = $actesEnvelopeSQL->create(1, $filename);
        $actesEnvelopeSQL->setTransactionInCloud($transaction_id);
        $this->getObjectInstancier()->set(ActesWorkspace::class, $this->workspace);
        $actesEnvelopeStorage = $this->getObjectInstancier()->get(ActesEnvelopeStorage::class);
        $actesEnvelopeStorage->grandMenage(self::MIN_DATE, $this->dateTomorrow, false);
        $testHandler = $this->getObjectInstancier()->get(TestHandler::class);
        static::assertTrue(file_exists("$actes_files_upload_root/$filename"));
        static::assertSame(
            "File $filename will be deleted if confirm is ok",
            $testHandler->getRecords()[3][self::MESSAGE]
        );
    }

    public function testDeleteIfIsInCloud()
    {
        $actesEnvelopeSQL = $this->getObjectInstancier()->get(ActesEnvelopeSQL::class);
        $filename = self::S2LOW_PHPUNIT_ACTE_ENVELOPE_STORAGE_TEST . mt_rand(0, mt_getrandmax());
        $actes_files_upload_root =  $this->workspace->getFilesUploadRoot();
        file_put_contents("$actes_files_upload_root/$filename", 'foo');
        $transaction_id = $actesEnvelopeSQL->create(1, $filename);
        static::assertFileExists("$actes_files_upload_root/$filename");
        $this->getObjectInstancier()->set(ActesWorkspace::class, $this->workspace);
        $this->getObjectInstancier()
            ->get(CloudStorageFactory::class)
            ->getInstanceByClassName(ActesCloudStorage::class)
            ->deleteIfIsInCloud($transaction_id);
        static::assertFileDoesNotExist("$actes_files_upload_root/$filename");
        $this->assertLogMessage("Deleting object #$transaction_id : $actes_files_upload_root/$filename");
    }

    /**
     * @throws Exception
     */
    public function testEnveloppeNotAvailable()
    {
        $actesEnvelopeSQL = $this->getObjectInstancier()->get(ActesEnvelopeSQL::class);

        $filename = self::S2LOW_PHPUNIT_ACTE_ENVELOPE_STORAGE_TEST . mt_rand(0, mt_getrandmax());

        $envelope_id = $actesEnvelopeSQL->create(1, $filename);
        $this->getObjectInstancier()->set(ActesWorkspace::class, $this->workspace);
        $this->getObjectInstancier()
            ->get(CloudStorageFactory::class)
            ->getInstanceByClassName(ActesCloudStorage::class)
            ->storeObject($envelope_id);

        $envelope_info = $actesEnvelopeSQL->getInfo($envelope_id);
        static::assertTrue($envelope_info['not_available']);
        static::assertFalse($envelope_info['is_in_cloud']);
    }

    /**
     * @throws Exception
     */
    public function testEnveloppeAvailable()
    {
        $actesEnvelopeSQL = $this->getObjectInstancier()->get(ActesEnvelopeSQL::class);

        $filename = self::S2LOW_PHPUNIT_ACTE_ENVELOPE_STORAGE_TEST . mt_rand(0, mt_getrandmax());
        $actes_files_upload_root =  $this->workspace->getFilesUploadRoot();
        file_put_contents("$actes_files_upload_root/$filename", 'foo');

        $envelope_id = $actesEnvelopeSQL->create(1, $filename);

        /** @var OpenStackSwiftWrapper | MockObject $openStackSwiftWrapper */
        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackSwiftWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $openStackSwiftWrapper->method('sendFile')->willReturn(true);
        $openStackSwiftWrapper->method('fileExistsOnCloud')->willReturn(true);

        $this->getObjectInstancier()->set(OpenStackSwiftWrapper::class, $openStackSwiftWrapper);
        $this->getObjectInstancier()->set(ActesWorkspace::class, $this->workspace);
        $this->getObjectInstancier()
            ->get(CloudStorageFactory::class)
            ->getInstanceByClassName(ActesCloudStorage::class)
            ->storeObject($envelope_id);

        $envelope_info = $actesEnvelopeSQL->getInfo($envelope_id);
        static::assertFalse($envelope_info['not_available']);
        static::assertTrue($envelope_info['is_in_cloud']);
    }

    public function testErrorSendingFile()
    {
        $actesEnvelopeSQL = $this->getObjectInstancier()->get(ActesEnvelopeSQL::class);

        $filename = self::S2LOW_PHPUNIT_ACTE_ENVELOPE_STORAGE_TEST . mt_rand(0, mt_getrandmax());
        $actes_files_upload_root =  $this->workspace->getFilesUploadRoot();
        file_put_contents($actes_files_upload_root . "/$filename", 'foo');

        $envelope_id = $actesEnvelopeSQL->create(1, $filename);

        /** @var OpenStackSwiftWrapper | MockObject $openStackSwiftWrapper */
        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackSwiftWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $openStackSwiftWrapper->method('sendFile')->willReturn(false);

        $this->getObjectInstancier()->set(OpenStackSwiftWrapper::class, $openStackSwiftWrapper);

        $this->getObjectInstancier()->set(ActesWorkspace::class, $this->workspace);
        $storeResult = $this->getObjectInstancier()
            ->get(CloudStorageFactory::class)
            ->getInstanceByClassName(ActesCloudStorage::class)
            ->storeObject($envelope_id);

        static::assertFalse($storeResult);

        $envelope_info = $actesEnvelopeSQL->getInfo($envelope_id);
        static::assertFalse($envelope_info['not_available']);
        static::assertFalse($envelope_info['is_in_cloud']);
    }
}
