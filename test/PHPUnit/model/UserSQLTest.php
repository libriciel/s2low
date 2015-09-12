<?php

require_once __DIR__."/../init.php";

class UserSQLTest extends S2lowTestCase {

	/**
	 * @var UserSQL
	 */
	private $userSQL;

	protected function setUp(){
		parent::setUp();
		$this->userSQL = new UserSQL($this->getSQLQuery());
	}

	public function testGetInfo(){
		$info = $this->userSQL->getInfo(1);
		$this->assertEquals("Eric",$info['givenname']);
	}

	public function testGetInfoNoInfo(){
		$this->assertEmpty($this->userSQL->getInfo(-1));
	}

	public function testGetNbUserWithMyCertificate(){
		$this->assertEquals(2,$this->userSQL->getNbUserWithMyCertificate('adullact','adullact'));
	}

	public function testGetInfoFromCertificateInfo(){
		$certificateInfo = array('subject'=>'adullact','issuer'=>'adullact');
		$info = $this->userSQL->getInfoFromCertificateInfo($certificateInfo);
		$this->assertEquals("Alice",$info[0]['givenname']);
	}

	public function testGetRoleStr(){
		$this->assertEquals("Super administrateur",$this->userSQL->getRoleStr("SADM"));
	}

	public function testGetDIAUser(){
		$this->assertEmpty($this->userSQL->getDIAUser(1));
	}

	public function testGetIdentificationMethode(){
		$this->assertEquals(UserSQL::IDENT_METHOD_CERT_ONLY,$this->userSQL->getIdentificationMethod(1));
	}

	public function testGetIdentificationMethodeNone(){
		$this->assertEquals(UserSQL::IDENT_METHOD_NONE,$this->userSQL->getIdentificationMethod(-1));
	}

	public function testGetIdentificationMethodeRGS(){
		$this->assertEquals(UserSQL::IDENT_METHOD_RGS_2_ETOILES,$this->userSQL->getIdentificationMethod(4));
	}

	public function testGetIdentificationMethodeLogin(){
		$this->assertEquals(UserSQL::IDENT_METHOD_LOGIN,$this->userSQL->getIdentificationMethod(2));
	}

	public function testGetIdentificationMethodeLibelle(){
		$this->assertEquals("aucune",$this->userSQL->getIdentificationMethodeLibelle(UserSQL::IDENT_METHOD_NONE));
	}

	public function testSaveCertificateRGS2Etoile(){
		$this->userSQL->saveCertificateRGS2Etoiles(1,"pem_content");
		$info = $this->userSQL->getInfo(1);
		$this->assertEquals("pem_content",$info['certificate_rgs_2_etoiles']);
	}

	public function testDeleteCertificateRGS2Etoile(){
		$this->userSQL->deleteCertificateRGS2Etoiles(4);
		$info = $this->userSQL->getInfo(4);
		$this->assertEmpty($info['certificate_rgs_2_etoiles']);
	}

	public function testUpdateCertificateIfNull(){
		$sql = "UPDATE users SET certificate_rgs_2_etoiles=NULL WHERE id=?";
		$this->getSQLQuery()->query($sql,1);
		$this->userSQL->updateCertificatRGS2EtoilesIfNull(1);
		$info = $this->userSQL->getInfo(1);
		$this->assertEmpty($info['certificate_rgs_2_etoiles']);
	}

	public function testGetIdFromConnexionInfo(){
		$this->assertEquals(array(2),$this->userSQL->getIdFromConnexionInfo('adullact','adullact','','alice','alice'));
	}

	public function testGetListIdFromConnexion(){
		$this->assertEquals(array(2,3),$this->userSQL->getListIdFromConnexion('adullact','adullact',''));
	}
}

