<?php

use GuzzleHttp\Psr7\Stream;
use OpenStack\ObjectStore\v1\Models\Container;
use OpenStack\ObjectStore\v1\Models\StorageObject;
use OpenStack\ObjectStore\v1\Service;
use OpenStack\OpenStack;
use PHPUnit\Framework\MockObject\MockObject;

class ActesEnvelopeStorageTest extends S2lowTestCase {
    private const S2LOW_PHPUNIT_ACTE_ENVELOPE_STORAGE_TEST = "s2low-phpunit-acte-envelope-storage-test";
    private const ACTES_FILES_UPLOAD_ROOT = 'actes_files_upload_root';
    private const MIN_DATE = "1970-01-01";
    private const MESSAGE = 'message';

    /** @var string */
    private $dateTomorrow;

    /**
	 * @throws Exception
	 */
	public function setUp() : void {
		parent::setUp();

		$this->dateTomorrow = date("Y-m-d",strtotime("tomorrow"));

		$openStackContainersManager =
			$this->getMockBuilder(OpenStackContainersStore::class)
				->disableOriginalConstructor()
				->getMock();

		$openStackContainersManager->expects($this->never())
            ->method($this->anything());

		$openStackSwiftWrapper = $this->getMockBuilder(OpenStackSwiftWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $openStackSwiftWrapper
            ->expects($this->never())
            ->method("retrieveFile");

        $openStackSwiftWrapper
            ->expects($this->never())
            ->method("deleteFile");

		$openStackSwiftWrapper
            ->method("fileExistsOnCloud")
            ->willReturn(true);

		$this->getObjectInstancier()->set(OpenStackSwiftWrapper::class,$openStackSwiftWrapper);
	}


	public function testGrandMenage(){
        $actesEnvelopeSQL = $this->getObjectInstancier()->get(ActesEnvelopeSQL::class);

		$filename = self::S2LOW_PHPUNIT_ACTE_ENVELOPE_STORAGE_TEST .mt_rand(0,mt_getrandmax());

		$actes_files_upload_root =  $this->getObjectInstancier()->get(self::ACTES_FILES_UPLOAD_ROOT);
		file_put_contents($actes_files_upload_root."/$filename","foo");

		$transaction_id = $actesEnvelopeSQL->create(1, $filename);
		$actesEnvelopeSQL->setTransactionInCloud($transaction_id);
		$actesEnvelopeStorage = $this->getObjectInstancier()->get(ActesEnvelopeStorage::class);
		$actesEnvelopeStorage->grandMenage(self::MIN_DATE,$this->dateTomorrow,"ok");
		$testHandler = $this->getObjectInstancier()->get(Monolog\Handler\TestHandler::class);
		$this->assertFalse(file_exists($actes_files_upload_root."/$filename"));
		$this->assertEquals("File $filename deleted",$testHandler->getRecords()[3][self::MESSAGE]);
	}

	public function testGrandMenageFileNotExists(){
		$actesEnvelopeSQL = $this->getObjectInstancier()->get(ActesEnvelopeSQL::class);
		$filename = self::S2LOW_PHPUNIT_ACTE_ENVELOPE_STORAGE_TEST .mt_rand(0,mt_getrandmax());

		$transaction_id = $actesEnvelopeSQL->create(1, $filename);
		$actesEnvelopeSQL->setTransactionInCloud($transaction_id);
		$actesEnvelopeStorage = $this->getObjectInstancier()->get(ActesEnvelopeStorage::class);
		$actesEnvelopeStorage->grandMenage(self::MIN_DATE,$this->dateTomorrow,true);
		$testHandler = $this->getObjectInstancier()->get("Monolog\Handler\TestHandler");
		$this->assertEquals("File not exists $filename [PASS]",$testHandler->getRecords()[2][self::MESSAGE]);
	}

	public function testGrandMenageNotConfirm(){
		$actesEnvelopeSQL = $this->getObjectInstancier()->get(ActesEnvelopeSQL::class);

		$filename = self::S2LOW_PHPUNIT_ACTE_ENVELOPE_STORAGE_TEST .mt_rand(0,mt_getrandmax());

		$actes_files_upload_root =  $this->getObjectInstancier()->get(self::ACTES_FILES_UPLOAD_ROOT);
		file_put_contents($actes_files_upload_root."/$filename","foo");

		$transaction_id = $actesEnvelopeSQL->create(1, $filename);
		$actesEnvelopeSQL->setTransactionInCloud($transaction_id);
		$actesEnvelopeStorage = $this->getObjectInstancier()->get(ActesEnvelopeStorage::class);
		$actesEnvelopeStorage->grandMenage(self::MIN_DATE,$this->dateTomorrow,false);
		$testHandler = $this->getObjectInstancier()->get("Monolog\Handler\TestHandler");
		$this->assertTrue(file_exists($actes_files_upload_root."/$filename"));
		$this->assertEquals("File $filename will be deleted if confirm is ok",$testHandler->getRecords()[3][self::MESSAGE]);
	}

	public function testDeleteIfIsInCloud(){
		$filename = self::S2LOW_PHPUNIT_ACTE_ENVELOPE_STORAGE_TEST .mt_rand(0,mt_getrandmax());
		$actes_files_upload_root =  $this->getObjectInstancier()->get(self::ACTES_FILES_UPLOAD_ROOT);
		file_put_contents($actes_files_upload_root."/$filename","foo");

		$this->assertFileExists($actes_files_upload_root."/$filename");
		$this->getObjectInstancier()->get(ActesEnvelopeStorage::class)->deleteIfIsInCloud($filename);
		$this->assertFileNotExists($actes_files_upload_root."/$filename");
		$this->assertLogMessage("Deleting Actes : $filename");
	}

	/**
	 * @throws Exception
	 */
	public function testEnveloppeNotAvailable(){
		$actesEnvelopeSQL = $this->getObjectInstancier()->get(ActesEnvelopeSQL::class);

		$filename = self::S2LOW_PHPUNIT_ACTE_ENVELOPE_STORAGE_TEST .mt_rand(0,mt_getrandmax());

		$envelope_id = $actesEnvelopeSQL->create(1, $filename);

		$actesEnvelopeStorage = $this->getObjectInstancier()->get(ActesEnvelopeStorage::class);

		$actesEnvelopeStorage->storeNextFileById($envelope_id);

		$envelope_info = $actesEnvelopeSQL->getInfo($envelope_id);
		$this->assertTrue($envelope_info['not_available']);
		$this->assertFalse($envelope_info['is_in_cloud']);
	}

	/**
	 * @throws Exception
	 */
	public function testEnveloppeAvailable(){
		$actesEnvelopeSQL = $this->getObjectInstancier()->get(ActesEnvelopeSQL::class);

		$filename = self::S2LOW_PHPUNIT_ACTE_ENVELOPE_STORAGE_TEST .mt_rand(0,mt_getrandmax());
		$actes_files_upload_root =  $this->getObjectInstancier()->get(self::ACTES_FILES_UPLOAD_ROOT);
		file_put_contents($actes_files_upload_root."/$filename","foo");

		$envelope_id = $actesEnvelopeSQL->create(1, $filename);

		$actesEnvelopeStorage = $this->getObjectInstancier()->get(ActesEnvelopeStorage::class);

		$actesEnvelopeStorage->storeNextFileById($envelope_id);

		$envelope_info = $actesEnvelopeSQL->getInfo($envelope_id);
		$this->assertFalse($envelope_info['not_available']);
		$this->assertTrue($envelope_info['is_in_cloud']);
	}
}