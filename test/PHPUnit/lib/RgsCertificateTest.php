<?php


class RgsCertificateTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var RgsCertificate
	 */
	private $rgsCertificate;

	protected function setUp(){
		parent::setUp();
		$validca_path = __DIR__."/fixtures/test";
		$this->rgsCertificate = new RgsCertificate(OPENSSL_PATH,$validca_path);
	}

	public function testVerify(){
		$x509_pem_certificate = file_get_contents(__DIR__."/fixtures/test/MyRootCA.pem");
		$this->assertTrue($this->rgsCertificate->isRgsCertificate($x509_pem_certificate));
	}

	public function testVerifyBadCertificat(){
		$x509_pem_certificate = file_get_contents(__DIR__."/fixtures/clean_pem.pem");
		$this->assertFalse($this->rgsCertificate->isRgsCertificate($x509_pem_certificate));
		$this->assertRegExp("#unable to get local issuer certificate#",$this->rgsCertificate->getLastMessage());
	}


}