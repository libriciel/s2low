<?php
class NounceSQLTest extends S2lowTestCase {

	/** @var  NounceSQL */
	private $nounceSQL;

	protected function setUp() {
		parent::setUp();
		$this->nounceSQL = $this->getObjectInstancier()->{'NounceSQL'};
	}

	public function testGetNounce(){
		$nounce = $this->nounceSQL->create("toto","MonMotDePasse");
		$this->assertNotEmpty($nounce);
	}

	public function testMenage(){
		$sql = "INSERT into nounce(creation) VALUES (?)";
		$this->getSQLQuery()->query($sql,date("c",strtotime("now -1 hours")));
		$sql = "SELECT count(*) FROM nounce";
		$this->assertEquals(1,$this->getSQLQuery()->queryOne($sql));
		$this->nounceSQL->menage();
		$this->assertEquals(0,$this->getSQLQuery()->queryOne($sql));
	}

	public function testVerify(){
		$nounce = $this->nounceSQL->create("toto","MonMotDePasse");
		$hash = hash("sha256","MonMotDePasse:$nounce");
		$this->assertTrue($this->nounceSQL->verify("toto",$nounce,$hash));
	}

	public function testVerifyFalse(){
		$nounce = $this->nounceSQL->create("toto","MonMotDePasse");
		$hash = "badhash";
		$this->assertFalse($this->nounceSQL->verify("toto",$nounce,$hash));
	}


}