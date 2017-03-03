<?php

class HeliosTransactionSQLTest extends S2lowTestCase {

	const FILENAME = "pes_aller.xml";

	/**
	 * @var HeliosTransactionsSQL
	 */
	private $heliosTransactionSQL;

	private $transaction_id;

	protected function setUp(){
		parent::setUp();
		$this->heliosTransactionSQL = new HeliosTransactionsSQL($this->getSQLQuery());
		$this->transaction_id = $this->heliosTransactionSQL->create(self::FILENAME,"aaa",8,1,42,123456789);
		$this->heliosTransactionSQL->updateStatus($this->transaction_id,HeliosTransactionsSQL::POSTE,"test");
	}

	public function testGetInfo(){
		$info = $this->heliosTransactionSQL->getInfo($this->transaction_id);
		$this->assertEquals(self::FILENAME,$info['filename']);
	}

	public function testUpdateStatus(){
		$this->heliosTransactionSQL->updateStatus($this->transaction_id,HeliosTransactionsSQL::ANNULE,"Test");
		$info = $this->heliosTransactionSQL->getInfo($this->transaction_id);
		$this->assertEquals(HeliosTransactionsSQL::ANNULE,$info['last_status_id']);
	}

	public function testGetLastestStatusId(){
		$status_id = $this->heliosTransactionSQL->getLatestStatusId($this->transaction_id);
		$this->assertEquals(HeliosTransactionsSQL::POSTE,$status_id);
	}

	public function testSAETransferIdentifier(){
		$this->heliosTransactionSQL->setSAETransferIdentifier($this->transaction_id,"azerty");
		$info = $this->heliosTransactionSQL->getInfo($this->transaction_id);
		$this->assertEquals("azerty",$info['sae_transfer_identifier']);
	}

	public function testGetArchiveFormStatusWithSAE(){
		$this->assertEmpty($this->heliosTransactionSQL->getArchiveFromStatusWithSAE(HeliosTransactionsSQL::EN_TRAITEMENT));
	}

	public function testSetArchiveURL(){
		$this->heliosTransactionSQL->setArchiveURL($this->transaction_id,"http://www.google.fr");
		$info = $this->heliosTransactionSQL->getInfo($this->transaction_id);
		$this->assertEquals("http://www.google.fr",$info['archive_url']);
	}

	public function testGetTransationToDelete(){
		$this->heliosTransactionSQL->updateStatus($this->transaction_id,HeliosTransactionsSQL::ACCEPTE_SAE,"test");
		$transaction_list = $this->heliosTransactionSQL->getTransactionToDelete();
		$this->assertEquals($this->transaction_id,$transaction_list[0]['id']);
	}

	public function testUpdateLastStatusId(){
		$this->expectOutputString("{$this->transaction_id} : ".HeliosTransactionsSQL::POSTE."\n");
		$sql = "UPDATE helios_transactions SET last_status_id=NULL";
		$this->getSQLQuery()->query($sql);
		$this->heliosTransactionSQL->updateLastStatusId();
		$status_id = $this->heliosTransactionSQL->getLatestStatusId($this->transaction_id);
		$this->assertEquals(HeliosTransactionsSQL::POSTE,$status_id);
	}

	public function testSetLastStatusId(){
		$this->heliosTransactionSQL->setLastStatusId($this->transaction_id);
		$status_id = $this->heliosTransactionSQL->getLatestStatusId($this->transaction_id);
		$this->assertEquals(HeliosTransactionsSQL::POSTE,$status_id);
	}

	public function testGetIdByStatus(){
		$id_list = $this->heliosTransactionSQL->getIdsByStatus(HeliosTransactionsSQL::POSTE);
		$this->assertEquals($this->transaction_id,$id_list[0]);
	}

	public function testGetIdByNomFic(){
		$this->heliosTransactionSQL->setNomFic($this->transaction_id,"toto");
		$id_list = $this->heliosTransactionSQL->getIdByNomFic("toto");
		$this->assertEquals($this->transaction_id,$id_list);
	}

	public function testNomFicExists(){
		$this->heliosTransactionSQL->setInfoFromPESAller(
			$this->transaction_id,
			array(
				'nom_fic'=>'toto',
				'cod_bud' => 42,
				'cod_col' => 123,
				'id_post' => 777
			)
			);
		$this->assertEquals(1,$this->heliosTransactionSQL->nomFicExists("toto",123));
	}

	public function testSetCompleteName(){
		$this->heliosTransactionSQL->setCompleteName($this->transaction_id,"toto");
		$info = $this->heliosTransactionSQL->getInfo($this->transaction_id);
		$this->assertEquals("toto",$info['complete_name']);
	}

	public function testMustSendWarning(){
		$this->assertFalse($this->heliosTransactionSQL->mustSendWarning($this->transaction_id));
	}

	public function testSetSendWarning(){
		$this->heliosTransactionSQL->setSendWarning($this->transaction_id);
		$this->assertFalse($this->heliosTransactionSQL->mustSendWarning($this->transaction_id));
	}

	public function testDelete(){
		$this->heliosTransactionSQL->delete($this->transaction_id);
		$this->assertEmpty($this->heliosTransactionSQL->getInfo($this->transaction_id));
	}

	public function testSetAquitFilename(){
		$this->heliosTransactionSQL->setAcquitFilename($this->transaction_id,self::FILENAME);
		$info = $this->heliosTransactionSQL->getInfo($this->transaction_id);
		$this->assertEquals(self::FILENAME,$info['acquit_filename']);
	}

	public function testGetAll(){
		$info = $this->heliosTransactionSQL->getAll();
		$this->assertEquals($this->transaction_id,$info[0]['id']);
	}

	public function testGetWorkflow(){
		$info = $this->heliosTransactionSQL->getWorkflow($this->transaction_id);
		$this->assertEquals(HeliosTransactionsSQL::POSTE,$info[0]['status_id']);
	}

	public function testIsDuplicate(){
		$this->assertTrue((boolean)$this->heliosTransactionSQL->isDuplicate("aaa"));
	}


}