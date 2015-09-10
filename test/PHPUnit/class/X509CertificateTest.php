<?php

class X509CertificateTest extends PHPUnit_Framework_TestCase {

	public function testPemCleaning(){
		$not_clean_pem = file_get_contents(__DIR__."/fixtures/pem_with_text.pem");
		
		$x509Certificate = new X509Certificate();
		$clean_pem = $x509Certificate->pemClean($not_clean_pem);
		
		$this->assertEquals(file_get_contents(__DIR__."/fixtures/clean_pem.pem"),$clean_pem);
	}

	
	public function testPemCleaningBadData(){
		$x509Certificate = new X509Certificate();
		$this->setExpectedException("Exception","Impossible de lire le certificat");
		$clean_pem = $x509Certificate->pemClean("not a pem file");
	}
	
}