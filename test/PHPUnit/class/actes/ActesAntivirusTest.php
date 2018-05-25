<?php

require_once __DIR__."/ActesCreator.php";

class ActesAntivirusTest extends S2lowTestCase {

	/** @var  TmpFolder */
	private $tmpFolder;
	private $tmp_dir;

	private $transaction_id;
	/**
	 * @throws Exception
	 */
	protected function setUp(){
		parent::setUp();
		$this->tmpFolder = new TmpFolder();
		$this->tmp_dir = $this->tmpFolder->create();
		$actesCreator = $this->getObjectInstancier()->get(ActesCreator::class);
		$this->transaction_id = $actesCreator->createTransaction(
			ActesStatusSQL::STATUS_POSTE,
			__DIR__."/fixtures/abc-TACT--000000000--20170803-16.tar.gz",
			$this->tmp_dir
		);
	}

	protected function tearDown() {
		$this->tmpFolder->delete($this->tmp_dir);
		parent::tearDown();
	}

	/**
	 * @throws Exception
	 */
	public function testOK(){
		$antivirus = $this->getMockBuilder(Antivirus::class)->getMock();
		$antivirus->expects($this->any())
			->method("checkArchiveSanity")
			->willReturn(true);
		$this->getObjectInstancier()->set(Antivirus::class,$antivirus);
		$actesAntivirus = $this->getObjectInstancier()->get(ActesAntivirus::class);
		$this->assertTrue($actesAntivirus->check($this->transaction_id));
	}

	/**
	 * @throws Exception
	 */
	public function testFailed(){
		$antivirus = $this->getMockBuilder(Antivirus::class)->getMock();
		$antivirus->expects($this->any())
			->method("checkArchiveSanity")
			->willReturn(false);

		$this->getObjectInstancier()->set(Antivirus::class,$antivirus);

		$actesAntivirus = $this->getObjectInstancier()->get(ActesAntivirus::class);
		$this->assertFalse($actesAntivirus->check($this->transaction_id));
	}

	/**
	 * @throws Exception
	 */
	public function testRaiseException(){
		$antivirus = $this->getMockBuilder(Antivirus::class)->getMock();
		$antivirus->expects($this->any())
			->method("checkArchiveSanity")
			->willThrowException(new Exception("testing"));

		$this->getObjectInstancier()->set(Antivirus::class,$antivirus);

		$actesAntivirus = $this->getObjectInstancier()->get(ActesAntivirus::class);
		$this->setExpectedException(Exception::class,"testing");
		$actesAntivirus->check($this->transaction_id);
	}



}