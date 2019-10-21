<?php

class PesAllerTest extends PHPUnit_Framework_TestCase {

	private $pesAller;

	protected function setUp() : void {
		parent::setUp();
		$this->pesAller = new PesAller();
	}

	public function testGetPmsg(){
		$pes_aller_path = __DIR__."/fixtures/HELIOS_SIMU_ALR2_1444811220_681372666.xml";
		$this->assertEquals("PES#123#034000#12",$this->pesAller->getP_MSG($pes_aller_path));
	}

	public function testGetPmsgBadPesAller(){
		$pes_aller_path = __DIR__."/fixtures/test.xml";
		$this->setExpectedException("Exception","La balise EnTetePES/CodCol n'est pas présente ou est vide");
		$this->pesAller->getP_MSG($pes_aller_path);
	}

}