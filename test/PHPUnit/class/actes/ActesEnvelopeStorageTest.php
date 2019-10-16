<?php

class ActesEnvelopeStorageTest extends S2lowTestCase {

	/**
	 * @throws Exception
	 */
	public function setUp(){
		parent::setUp();
		$content =
			$this->getMockBuilder(\Guzzle\Http\EntityBody::class)
				->disableOriginalConstructor()
				->getMock();

		$dataObject =
			$this->getMockBuilder(\OpenCloud\ObjectStore\Resource\DataObject::class)
				->disableOriginalConstructor()
				->getMock();

		$dataObject
			->expects($this->any())
			->method("getContent")
			->willReturn($content);

		$container =
			$this->getMockBuilder("OpenCloud\ObjectStore\Resource\Container")
				->disableOriginalConstructor()
				->getMock();

		$container
			->expects($this->any())
			->method("getObject")
			->willReturn($dataObject);

		$container
			->expects($this->any())
			->method("objectExists")
			->willReturn(true);

		$service =
			$this->getMockBuilder("\OpenCloud\ObjectStore\Service")
				->disableOriginalConstructor()
				->getMock();

		$service
			->expects($this->any())
			->method("getContainer")
			->willReturn($container);

		$openStack =
			$this->getMockBuilder("\OpenCloud\OpenStack")
				->disableOriginalConstructor()
				->getMock();

		$openStack
			->expects($this->any())
			->method("objectStoreService")
			->willReturn($service);


		$openStackFactory =
			$this->getMockBuilder("OpenStackFactory")
				->disableOriginalConstructor()
				->getMock();

		$openStackFactory
			->expects($this->any())
			->method("getInstance")
			->willReturn($openStack);
		/** @var OpenStackFactory $openStackFactory */

		$openStackSwiftWrapper = new OpenStackSwiftWrapper(
			$openStackFactory,
			$this->getObjectInstancier()->get('Monolog\Logger')
		);

		$this->getObjectInstancier()->set(OpenStackSwiftWrapper::class,$openStackSwiftWrapper);
	}


	/**
	 * @throws Exception
	 */
	public function teststoreAllFile(){

		$actesEnvelopeStorage = $this->getObjectInstancier()->get("ActesEnvelopeSQL");

		$filename = "s2low-phpunit-acte-envelope-storage-test".mt_rand(0,mt_getrandmax());

		$actes_files_upload_root =  $this->getObjectInstancier()->get('actes_files_upload_root');
		file_put_contents($actes_files_upload_root."/$filename","foo");

		$actesEnvelopeStorage->create(1, $filename);

		$actesEnvelopeStorage = $this->getObjectInstancier()->get(ActesEnvelopeStorage::class);

		$actesEnvelopeStorage->storeAll();

		$testHandler = $this->getObjectInstancier()->get("Monolog\Handler\TestHandler");
		$this->assertEquals(
			"Storing file $actes_files_upload_root/$filename",
			$testHandler->getRecords()[1]['message']
		);
	}

	public function testGrandMenage(){
		$actesEnvelopeSQL = $this->getObjectInstancier()->get("ActesEnvelopeSQL");

		$filename = "s2low-phpunit-acte-envelope-storage-test".mt_rand(0,mt_getrandmax());

		$actes_files_upload_root =  $this->getObjectInstancier()->get('actes_files_upload_root');
		file_put_contents($actes_files_upload_root."/$filename","foo");

		$transaction_id = $actesEnvelopeSQL->create(1, $filename);
		$actesEnvelopeSQL->setTransactionInCloud($transaction_id);
		$actesEnvelopeStorage = $this->getObjectInstancier()->get(ActesEnvelopeStorage::class);
		$actesEnvelopeStorage->grandMenage("1970-01-01",date("Y-m-d",strtotime("tomorrow")),"ok");
		$testHandler = $this->getObjectInstancier()->get("Monolog\Handler\TestHandler");
		$this->assertFalse(file_exists($actes_files_upload_root."/$filename"));
		$this->assertEquals("File $filename deleted",$testHandler->getRecords()[3]['message']);
	}

	public function testGrandMenageFileNotExists(){
		$actesEnvelopeSQL = $this->getObjectInstancier()->get("ActesEnvelopeSQL");
		$filename = "s2low-phpunit-acte-envelope-storage-test".mt_rand(0,mt_getrandmax());

		$transaction_id = $actesEnvelopeSQL->create(1, $filename);
		$actesEnvelopeSQL->setTransactionInCloud($transaction_id);
		$actesEnvelopeStorage = $this->getObjectInstancier()->get(ActesEnvelopeStorage::class);
		$actesEnvelopeStorage->grandMenage("1970-01-01",date("Y-m-d",strtotime("tomorrow")),true);
		$testHandler = $this->getObjectInstancier()->get("Monolog\Handler\TestHandler");
		$this->assertEquals("File not exists $filename [PASS]",$testHandler->getRecords()[2]['message']);
	}

	public function testGrandMenageNotConfirm(){
		$actesEnvelopeSQL = $this->getObjectInstancier()->get("ActesEnvelopeSQL");

		$filename = "s2low-phpunit-acte-envelope-storage-test".mt_rand(0,mt_getrandmax());

		$actes_files_upload_root =  $this->getObjectInstancier()->get('actes_files_upload_root');
		file_put_contents($actes_files_upload_root."/$filename","foo");

		$transaction_id = $actesEnvelopeSQL->create(1, $filename);
		$actesEnvelopeSQL->setTransactionInCloud($transaction_id);
		$actesEnvelopeStorage = $this->getObjectInstancier()->get(ActesEnvelopeStorage::class);
		$actesEnvelopeStorage->grandMenage("1970-01-01",date("Y-m-d",strtotime("tomorrow")),false);
		$testHandler = $this->getObjectInstancier()->get("Monolog\Handler\TestHandler");
		$this->assertTrue(file_exists($actes_files_upload_root."/$filename"));
		$this->assertEquals("File $filename will be deleted if confirm is ok",$testHandler->getRecords()[3]['message']);
	}

	public function testDeleteIfIsInCloud(){
		$filename = "s2low-phpunit-acte-envelope-storage-test".mt_rand(0,mt_getrandmax());
		$actes_files_upload_root =  $this->getObjectInstancier()->get('actes_files_upload_root');
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

		$filename = "s2low-phpunit-acte-envelope-storage-test".mt_rand(0,mt_getrandmax());

		$envelope_id = $actesEnvelopeSQL->create(1, $filename);
		$envelope_info = $actesEnvelopeSQL->getInfo($envelope_id);

		$actesEnvelopeStorage = $this->getObjectInstancier()->get(ActesEnvelopeStorage::class);

		$actesEnvelopeStorage->storeNextFile($envelope_info);

		$envelope_info = $actesEnvelopeSQL->getInfo($envelope_id);
		$this->assertTrue($envelope_info['not_available']);
		$this->assertFalse($envelope_info['is_in_cloud']);
	}

	/**
	 * @throws Exception
	 */
	public function testEnveloppeAvailable(){
		$actesEnvelopeSQL = $this->getObjectInstancier()->get(ActesEnvelopeSQL::class);

		$filename = "s2low-phpunit-acte-envelope-storage-test".mt_rand(0,mt_getrandmax());
		$actes_files_upload_root =  $this->getObjectInstancier()->get('actes_files_upload_root');
		file_put_contents($actes_files_upload_root."/$filename","foo");

		$envelope_id = $actesEnvelopeSQL->create(1, $filename);
		$envelope_info = $actesEnvelopeSQL->getInfo($envelope_id);

		$actesEnvelopeStorage = $this->getObjectInstancier()->get(ActesEnvelopeStorage::class);

		$actesEnvelopeStorage->storeNextFile($envelope_info);

		$envelope_info = $actesEnvelopeSQL->getInfo($envelope_id);
		$this->assertFalse($envelope_info['not_available']);
		$this->assertTrue($envelope_info['is_in_cloud']);

	}


}