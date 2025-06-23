<?php

declare(strict_types=1);

namespace PHPUnit\class\actes;

use Exception;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Psr\Log\LoggerInterface;
use S2lowLegacy\Class\actes\ActesCloudStorable;
use S2lowLegacy\Class\actes\ActesCloudStorage;
use S2lowLegacy\Class\actes\ActesEnvelopeSQL;
use S2lowLegacy\Class\actes\ActesMenage;
use S2lowLegacy\Class\ICloudStorable;
use S2lowLegacy\Lib\OpenStackContainerStore;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;
use PHPUnit\Framework\MockObject\MockObject;
use S2lowTestCase;

class ActesMenageTest extends S2lowTestCase
{
    private const S2LOW_PHPUNIT_ACTE_ENVELOPE_STORAGE_TEST = 's2low-phpunit-acte-envelope-storage-test';
    private const MIN_DATE = '1970-01-01';
    private const MESSAGE = 'message';
    private string $dateTomorrow;

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
    }

    public function testGrandMenage()
    {
        $actesEnvelopeSQL = $this->getObjectInstancier()->get(ActesEnvelopeSQL::class);

        $filename = self::S2LOW_PHPUNIT_ACTE_ENVELOPE_STORAGE_TEST . mt_rand(0, mt_getrandmax());

        $actes_files_upload_root =  $this->getObjectInstancier()->getParameter('app.actes.files_upload_root');
        file_put_contents("$actes_files_upload_root/$filename", 'foo');

        $transaction_id = $actesEnvelopeSQL->create(113, $filename);
        $actesEnvelopeSQL->setTransactionInCloud($transaction_id);
        $actesMenage = $this->getActesMenage();
        $actesMenage->grandMenage(self::MIN_DATE, $this->dateTomorrow, 'ok');
        static::assertFalse(file_exists($actes_files_upload_root . "/$filename"));

        static::assertTrue(
            $this->testHandler->hasRecord(
                "File $filename deleted",
                Level::Info
            )
        );
    }

    public function testGrandMenageFileNotExists()
    {
        $actesEnvelopeSQL = $this->getObjectInstancier()->get(ActesEnvelopeSQL::class);
        $filename = self::S2LOW_PHPUNIT_ACTE_ENVELOPE_STORAGE_TEST . mt_rand(0, mt_getrandmax());

        $transaction_id = $actesEnvelopeSQL->create(1, $filename);
        $actesEnvelopeSQL->setTransactionInCloud($transaction_id);
        $actesMenage = $this->getActesMenage();

        $actesMenage->grandMenage(self::MIN_DATE, $this->dateTomorrow, true);

        static::assertTrue(
            $this->testHandler->hasRecord(
                "File not exists $filename [PASS]",
                Level::Debug
            )
        );
    }

    public function testGrandMenageNotConfirm()
    {
        $actesEnvelopeSQL = $this->getObjectInstancier()->get(ActesEnvelopeSQL::class);

        $filename = self::S2LOW_PHPUNIT_ACTE_ENVELOPE_STORAGE_TEST . mt_rand(0, mt_getrandmax());

        $actes_files_upload_root =  $this->getObjectInstancier()->getParameter('app.actes.files_upload_root');
        file_put_contents($actes_files_upload_root . "/$filename", 'foo');

        $transaction_id = $actesEnvelopeSQL->create(1, $filename);
        $actesEnvelopeSQL->setTransactionInCloud($transaction_id);
        $actesMenage = $this->getActesMenage();
        $actesMenage->grandMenage(self::MIN_DATE, $this->dateTomorrow, false);
        static::assertTrue(file_exists("$actes_files_upload_root/$filename"));

        static::assertTrue(
            $this->testHandler->hasRecord(
                "File $filename will be deleted if confirm is ok",
                Level::Debug
            )
        );
    }

    public function testDeleteIfIsInCloud()
    {
        $actesEnvelopeSQL = $this->getObjectInstancier()->get(ActesEnvelopeSQL::class);
        $filename = self::S2LOW_PHPUNIT_ACTE_ENVELOPE_STORAGE_TEST . mt_rand(0, mt_getrandmax());
        $actes_files_upload_root =  $this->getObjectInstancier()->getParameter('app.actes.files_upload_root');
        file_put_contents("$actes_files_upload_root/$filename", 'foo');
        $transaction_id = $actesEnvelopeSQL->create(1, $filename);
        static::assertFileExists("$actes_files_upload_root/$filename");
        $actesCloudStorage = $this->getActesCloudStorage($actesEnvelopeSQL);
        $actesCloudStorage->deleteIfIsInCloud($transaction_id);
        static::assertFileDoesNotExist("$actes_files_upload_root/$filename");

        static::assertTrue(
            $this->testHandler->hasRecord(
                "Deleting object #$transaction_id : $actes_files_upload_root/$filename",
                Level::Info
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testEnveloppeNotAvailable()
    {
        $actesEnvelopeSQL = $this->getObjectInstancier()->get(ActesEnvelopeSQL::class);

        $filename = self::S2LOW_PHPUNIT_ACTE_ENVELOPE_STORAGE_TEST . mt_rand(0, mt_getrandmax());

        $envelope_id = $actesEnvelopeSQL->create(1, $filename);

        $this->getObjectInstancier()
            ->get(ActesCloudStorage::class)
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
        $actes_files_upload_root =  $this->getObjectInstancier()->getParameter('app.actes.files_upload_root');
        file_put_contents("$actes_files_upload_root/$filename", 'foo');

        $envelope_id = $actesEnvelopeSQL->create(1, $filename);

        /** @var OpenStackSwiftWrapper | MockObject $openStackSwiftWrapper */
        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackSwiftWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $openStackSwiftWrapper->method('sendFile')->willReturn(true);
        $openStackSwiftWrapper->method('fileExistsOnCloud')->willReturn(true);

        $acteCloudStorage = $this->getActesCloudStorage($actesEnvelopeSQL, $openStackSwiftWrapper);
        $acteCloudStorage->storeObject($envelope_id);

        $envelope_info = $actesEnvelopeSQL->getInfo($envelope_id);
        static::assertFalse($envelope_info['not_available']);
        static::assertTrue($envelope_info['is_in_cloud']);
    }

    public function testErrorSendingFile()
    {
        $actesEnvelopeSQL = $this->getObjectInstancier()->get(ActesEnvelopeSQL::class);

        $filename = self::S2LOW_PHPUNIT_ACTE_ENVELOPE_STORAGE_TEST . mt_rand(0, mt_getrandmax());
        $actes_files_upload_root =  $this->getObjectInstancier()->getParameter('app.actes.files_upload_root');
        file_put_contents($actes_files_upload_root . "/$filename", 'foo');

        $envelope_id = $actesEnvelopeSQL->create(1, $filename);

        /** @var OpenStackSwiftWrapper | MockObject $openStackSwiftWrapper */
        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackSwiftWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $openStackSwiftWrapper->method('sendFile')->willReturn(false);

        $acteCloudStorage = $this->getActesCloudStorage($actesEnvelopeSQL, $openStackSwiftWrapper);
        $storeResult = $acteCloudStorage->storeObject($envelope_id);

        static::assertFalse($storeResult);

        $envelope_info = $actesEnvelopeSQL->getInfo($envelope_id);
        static::assertFalse($envelope_info['not_available']);
        static::assertFalse($envelope_info['is_in_cloud']);
    }

    private function getActesMenage(): ActesMenage
    {
        return new ActesMenage(
            self::getContainer()->getParameter('app.actes.files_upload_root'),
            self::getContainer()->get(ActesEnvelopeSQL::class),
            self::getContainer()->get(OpenStackSwiftWrapper::class),
            $this->logger
        );
    }

    private function getActesCloudStorage(ActesEnvelopeSQL $acteEnvelopeSQL, OpenStackSwiftWrapper $openStackSwiftWrapper = null): ActesCloudStorage
    {
        $acteCloudStorable = new ActesCloudStorable(
            self::getContainer()->getParameter('app.actes.files_upload_root'),
            $acteEnvelopeSQL,
            $this->tmpPathFolder
        );

        return new ActesCloudStorage(
            $acteCloudStorable,
            $openStackSwiftWrapper ?? self::getContainer()->get(OpenStackSwiftWrapper::class),
            $this->logger,
            openstack_enable: false
        );
    }
}
