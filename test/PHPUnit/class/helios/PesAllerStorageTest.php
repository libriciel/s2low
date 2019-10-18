<?php

class PesAllerStorageTest extends S2lowTestCase {

	use HeliosUtilitiesTestTrait;

	const SHA1_EXEMPLE = "ab3321d34d3fb32b52332befa534c9854fff677b";

	/**
	 * @param bool $fileExistsOnCloud
	 * @return string
	 * @throws Exception
	 */
	private function mockOpenStackSwiftWrapper(bool $fileExistsOnCloud){

		$tmpFolder = new TmpFolder();
		$tmp_folder = $tmpFolder->create();
		$pes_aller_path = $tmp_folder."/".self::SHA1_EXEMPLE;
		file_put_contents($pes_aller_path,"<test></test>");

		$openStackSwiftWrapper = $this->getMockBuilder(OpenStackSwiftWrapper::class)
			->disableOriginalConstructor()
			->getMock();
		$openStackSwiftWrapper

			->method("fileExistsOnCloud")
			->willReturn($fileExistsOnCloud);
		$this->getObjectInstancier()->set(OpenStackSwiftWrapper::class,$openStackSwiftWrapper);
		$this->getObjectInstancier()->set('helios_files_upload_root',$tmp_folder);
		return $pes_aller_path;
	}

	/**
	 * @throws Exception
	 */
	public function testDelete(){
		$pes_aller_path = $this->mockOpenStackSwiftWrapper(true);
		$this->assertFileExists($pes_aller_path);
		$this->getObjectInstancier()->get(PesAllerStorage::class)->deleteIfIsInCloud(self::SHA1_EXEMPLE);
		$this->assertFileNotExists($pes_aller_path);
	}

	/**
	 * @throws Exception
	 */
	public function testDeleteNotOnCloud(){
		$pes_aller_path = $this->mockOpenStackSwiftWrapper(false);
		$this->assertFileExists($pes_aller_path);
		$this->getObjectInstancier()->get(PesAllerStorage::class)->deleteIfIsInCloud(self::SHA1_EXEMPLE);
		$this->assertFileExists($pes_aller_path);
	}


	/**
	 * @throws Exception
	 */
	public function testStoreNotAvailable(){
		$transaction_id = $this->createTransaction();
		$heliosTransactionsSQL = $this->getObjectInstancier()->get(HeliosTransactionsSQL::class);

		$transaction_info = $heliosTransactionsSQL->getInfo($transaction_id);

		$pesAllerStorage = $this->getObjectInstancier()->get(PesAllerStorage::class);
		$pesAllerStorage->storeNextFile($transaction_info);

		$transaction_info = $heliosTransactionsSQL->getInfo($transaction_id);
		$this->assertTrue($transaction_info['not_available']);
	}

}