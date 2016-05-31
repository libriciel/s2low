<?php

class RgsConnexionTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var RgsConnexion
	 */
	private $rgsConnexion;

	public function setUp(){
		parent::setUp();
		$this->rgsConnexion = new RgsConnexion();
	}

	public function testIsRgsConnexion(){
		$this->assertFalse($this->rgsConnexion->isRgsConnexion());
	}

	public function testIsRgsConnexionNotVerify(){
		$server['SSL_CLIENT_VERIFY'] = "ERROR";
		$this->rgsConnexion->setServerGlobal($server);
		$this->assertFalse($this->rgsConnexion->isRgsConnexion());
	}

	public function testIsRgsConnexionBadCertif(){
		$server['SSL_CLIENT_VERIFY'] = "SUCCESS";
		$server['SSL_CLIENT_CERT'] = "rogue certificate";
		$this->rgsConnexion->setServerGlobal($server);
		$this->assertFalse($this->rgsConnexion->isRgsConnexion());
		$this->assertRegExp("#unable to load certificat#",$this->rgsConnexion->getLastMessage());
	}

	public function testIsRgsConnexionOK(){
		$server['SSL_CLIENT_VERIFY'] = "SUCCESS";
		$server['SSL_CLIENT_CERT'] = file_get_contents(__DIR__."/../lib/fixtures/user1.pem");
		$this->rgsConnexion->setServerGlobal($server);
		$this->rgsConnexion->setRgsValidCaPath(__DIR__."/../lib/fixtures/validca/");
		$this->assertTrue($this->rgsConnexion->isRgsConnexion());
	}
}