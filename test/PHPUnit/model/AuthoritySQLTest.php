<?php

class AuthoritySQLTest extends S2lowTestCase {

	/**
	 * @var AuthoritySQL
	 */
	private $authoritySQL;

	public function setUp() : void {
		parent::setUp();
		$this->authoritySQL = new AuthoritySQL($this->getSQLQuery());
	}

	public function testGetInfo(){
		$info = $this->authoritySQL->getInfo(1);
		$this->assertEquals("Bourg-en-Bresse",$info['name']);
	}

	public function testGetIdBySiren(){
		$id = $this->authoritySQL->getIdBySIREN("123456789");
		$this->assertEquals(1,$id);
	}

	public function testGetBySiret(){
		$info = $this->authoritySQL->getBySIRET("42");
		$this->assertFalse($info);
	}

	public function testGetAll(){
		$info = $this->authoritySQL->getAll();
		$this->assertEquals("Bourg-en-Bresse",$info[1]);
	}

	public function testGetSAEProperties(){
		$this->authoritySQL->getSAEProperties();
		$this->noAssertion();
	}

	public function testGetSAEPropertiesType(){
		$this->assertEquals('text',$this->authoritySQL->getSAEPropertiesType('pastell_url'));
	}

	public function testUpdateSAE(){
	    $pastellProperties = new PastellProperties();
	    $pastellProperties->url = "test";
	    $pastellProperties->login = "login";
	    $pastellProperties->password = "password";
	    $pastellProperties->id_e = 42;
		$this->authoritySQL->updateSAE(1,$pastellProperties);
		$info = $this->authoritySQL->getInfo(1);
		$this->assertEquals("test",$info['pastell_url']);
        $this->assertEquals("password",$info['pastell_password']);
	}

	public function testVerifDepartementAndDistrict(){
		$this->assertEquals(0,$this->authoritySQL->verifDepartmentAndDistrict(999,001));
	}

	public function testGetList(){
		$this->assertEmpty($this->authoritySQL->getList(1,1,'toto','123','1234',0,10));
	}

	public function testGetNb(){
		$this->assertEquals(0,$this->authoritySQL->getNb(1,1,'toto','123',"1234"));
	}

	public function testGetAllGroup(){
		$result = $this->authoritySQL->getAllGroup(1);
		$this->assertEquals('Saint-Andre de Corcy',$result[2]);
	}

	public function testGetListInsensitive() {
		$this->assertEquals(1, count($this->authoritySQL->getList(false, false, "BOURG", false, false, 0, 10)));
	}

	public function testGetNbInsensitive() {
		$this->assertEquals(1, $this->authoritySQL->getNb(false, false, "BOURG", false, false));
	}

	public function testUpdateVerifNomFic(){
		$this->authoritySQL->updateDoNotVerifyNomFicUnicity(1,true);
		$info = $this->authoritySQL->getInfo(1);
		$this->assertTrue($info['helios_do_not_verify_nom_fic_unicity']);
	}

	public function testUpdateVerifNomFicFalse(){
		$this->authoritySQL->updateDoNotVerifyNomFicUnicity(1,false);
		$info = $this->authoritySQL->getInfo(1);
		$this->assertFalse($info['helios_do_not_verify_nom_fic_unicity']);
	}

	public function testGetAllForExport(){
		$info = $this->authoritySQL->getAllForExport();
		$this->assertEquals(array (
				0 =>
					array (
						'name' => 'Bourg-en-Bresse',
						'email' => NULL,
						'siren' => '123456789',
						'address' => NULL,
						'postal_code' => NULL,
						'city' => NULL,
						'telephone' => NULL,
						'fax' => NULL,
						'department' => '001',
						'district' => '1',
						'status' => 1,
						'group_name' => 'Groupe de test',
						'description' => 'Conseil régional',
					),
				1 =>
					array (
						'name' => 'Saint-Andre de Corcy',
						'email' => NULL,
						'siren' => '999999999',
						'address' => NULL,
						'postal_code' => NULL,
						'city' => NULL,
						'telephone' => NULL,
						'fax' => NULL,
						'department' => NULL,
						'district' => NULL,
						'status' => 1,
						'group_name' => 'Groupe de test',
						'description' => NULL,
					),
			)
		, $info);
	}

	public function testGetAllForExportGroupAdmin(){
		$info = $this->authoritySQL->getAllForExport(2);
		$this->assertEquals([], $info);
	}


}
