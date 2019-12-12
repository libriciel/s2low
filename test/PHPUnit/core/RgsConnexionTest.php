<?php

class RgsConnexionTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var RgsConnexion
	 */
	private $rgsConnexion;

	public function setUp() : void {
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
		$server['SSL_CLIENT_CERT'] = file_get_contents(__DIR__."/../controller/fixtures/contact@example.org.pem");
		$server['SSL_CLIENT_CERT_CHAIN_0'] = file_get_contents(__DIR__."/../controller/fixtures/ca_users_chaine.pem");
		$this->rgsConnexion->setServerGlobal($server);
		$this->rgsConnexion->setRgsValidCaPath("/etc/s2low/ssl/validca/");
		$this->assertTrue($this->rgsConnexion->isRgsConnexion());
	}
}