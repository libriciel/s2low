<?php

require_once __DIR__."/ActesCreator.php";

class ActesAntivirusTest extends S2lowTestCase {

	/** @var  TmpFolder */
	private $tmpFolder;
	private $tmp_dir;

	/**
	 * @throws Exception
	 */
	protected function setUp(){
		parent::setUp();
		$this->tmpFolder = new TmpFolder();
		$this->tmp_dir = $this->tmpFolder->create();
	}

	protected function tearDown() {
		$this->tmpFolder->delete($this->tmp_dir);
		parent::tearDown();
	}

	/**
	 * @throws Exception
	 */
	public function testOK(){
		$actesCreator = $this->getObjectInstancier()->get(ActesCreator::class);
		$transaction_id = $actesCreator->createTransaction(
			ActesStatusSQL::STATUS_POSTE,
			__DIR__."/fixtures/abc-TACT--000000000--20170803-16.tar.gz",
			$this->tmp_dir
		);

		$actesAntivirus = $this->getObjectInstancier()->get(ActesAntivirus::class);
		$actesAntivirus->check($transaction_id);
	}



}