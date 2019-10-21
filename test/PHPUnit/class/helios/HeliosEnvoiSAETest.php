<?php

class HeliosEnvoiSAETest extends S2lowTestCase {

	use HeliosUtilitiesTestTrait;
	use PastellConfigurationTestTrait;

	/**
	 * @return string
	 * @throws Exception
	 */
	private function mockOpenStack(){
		$tmpFolder = new TmpFolder();
		$tmp_folder = $tmpFolder->create();
		$pes_aller_path = $tmp_folder."/ab3321d34d3fb32b52332befa534c9854fff677b";
		file_put_contents($pes_aller_path,"<test></test>");
		$openStackSwiftWrapper = $this->getMockBuilder(OpenStackSwiftWrapper::class)
			->disableOriginalConstructor()
			->getMock();
		$openStackSwiftWrapper->method("fileExistsOnCloud")->willReturn(true);
		$openStackSwiftWrapper->method("retrieveFile")->willReturn($pes_aller_path);
		$this->getObjectInstancier()->set(OpenStackSwiftWrapper::class,$openStackSwiftWrapper);
		$this->getObjectInstancier()->set('helios_files_upload_root',$tmp_folder);
		return $pes_aller_path;
	}

	/**
	 * @throws Exception
	 */
	public function testSend(){
		$pes_aller_path = $this->mockOpenStack();
    	$this->mockPastellFactory();
		$transaction_id = $this->setTransactionEnattente();

		$this->assertFileExists($pes_aller_path);

		$this->assertTrue(
			$this->getObjectInstancier()->get(HeliosEnvoiSAE::class)->sendArchive($transaction_id)
		);

		$this->assertFileNotExists($pes_aller_path);
		$this->assertLogMessage("La transaction $transaction_id a été envoyé à Pastell",2);
	}

	private function setTransactionEnattente(){
    	$this->configurePastell();
		$transaction_id = $this->createTransaction();
		$this->getObjectInstancier()
			->get(HeliosPrepareEnvoiSAE::class)
			->setArchiveEnAttenteEnvoiSEA(1,$transaction_id);
		return $transaction_id;
	}

	/**
	 * @throws Exception
	 */
	public function testSendTransactionEnErreur(){
		$this->mockOpenStack();

		$this->mockPastellFactory(false,"Erreur renvoyé par le mock");
		$transaction_id = $this->setTransactionEnattente();

		$this->assertFalse(
			$this->getObjectInstancier()->get(HeliosEnvoiSAE::class)->sendArchive($transaction_id)
		);
		$this->assertLogMessage(
			"Le document n'a pas pu être envoyé sur Pastell : Erreur renvoyé par le mock",
			1
		);
	}

	/**
	 * @throws Exception
	 */
	public function testSendAllArchive(){
		$this->mockOpenStack();
		$this->mockPastellFactory("xyzt",false);
		$transaction_id = $this->setTransactionEnattente();
		$this->getObjectInstancier()->get(HeliosEnvoiSAE::class)->sendAllArchive();
		$this->assertLogMessage("1 transactions à envoyer...",2);
		$this->assertLogMessage("La transaction $transaction_id a été envoyé à Pastell",5);
	}

	/**
	 * @throws Exception
	 */
	public function testWhenErrorMessageIsTooLong(){
		$this->mockOpenStack();
		$error_message = str_repeat('X',1024);
		$this->mockPastellFactory(false,$error_message);
		$transaction_id = $this->setTransactionEnattente();
		$this->assertFalse(
			$this->getObjectInstancier()->get(HeliosEnvoiSAE::class)->sendArchive($transaction_id)
		);
		$this->assertLogMessage(
			"Le document n'a pas pu être envoyé sur Pastell : $error_message",
			1
		);

	}
}