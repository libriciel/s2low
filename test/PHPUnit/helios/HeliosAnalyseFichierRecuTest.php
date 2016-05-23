<?php

class HeliosAnalyseFichierRecuTest extends S2lowTestCase {

	private $helios_ftp_response_tmp_local_path;
	private $helios_response_root;
	private $helios_responses_error_path;
	private $helios_ocre;

	public function setUp(){
		parent::setUp();
		org\bovigo\vfs\vfsStream::setup('test');
		$testStreamUrl = org\bovigo\vfs\vfsStream::url('test');
		$this->helios_ftp_response_tmp_local_path = $testStreamUrl."/helios_ftp_response_tmp_local_path/";
		$this->helios_response_root = $testStreamUrl."/helios_response_root/";
		$this->helios_responses_error_path = $testStreamUrl."/helios_responses_error_path/";
		$this->helios_ocre = $testStreamUrl."/helios_ocre/";
		mkdir($this->helios_ftp_response_tmp_local_path);
		mkdir($this->helios_response_root);
		mkdir($this->helios_responses_error_path);
		mkdir($this->helios_ocre);
	}
	
	public function testAnalyseVide(){
		$this->expectOutputRegex('#Aucun fichier à analyser#');
		$this->analyse();
	}
	
	private function analyse(){
		$this->getHeliosAnalyseFichierReponse()->analyse(
			$this->helios_ftp_response_tmp_local_path,
			$this->helios_response_root,
			$this->helios_responses_error_path,
			$this->helios_ocre
		);
	}
	
	private function getHeliosAnalyseFichierReponse(){
		$heliosTransactionsSQL = new HeliosTransactionsSQL($this->getSQLQuery());
		$authoritySQL = new AuthoritySQL($this->getSQLQuery());
		$heliosRetourSQL = new HeliosRetourSQL($this->getSQLQuery());
		$authoritySiretSQL = new AuthoritySiretSQL($this->getSQLQuery());
		$schema_pes_path = HELIOS_XSD_PATH;
		return new HeliosAnalyseFichierRecu($heliosTransactionsSQL, $authoritySQL, $heliosRetourSQL, $authoritySiretSQL, $schema_pes_path,"noreply@sigmalis.com");
	}
	
	public function testAnalysePesRetour(){
		$authoritySireSQL = new AuthoritySiretSQL($this->getSQLQuery());
		$authoritySireSQL->add(1,"12345678900035");
		$this->expectOutputRegex('#Traitement de vfs://test/helios_ftp_response_tmp_local_path/pes_retour.xml#');
		$this->analysePesRetour(__DIR__."/fixtures/pes_retour.xml");
		$this->assertTrue(file_exists($this->helios_response_root."/pes_retour.xml"));
		$heliosRetourSQL = new HeliosRetourSQL($this->getSQLQuery());
		$info = $heliosRetourSQL->getInfoFromFilename(1, "pes_retour.xml");
		$this->assertEquals("12345678900035", $info['siret']);
	}
	
	private function analysePesRetour($file_path){
		$filename = basename($file_path);
		copy($file_path, $this->helios_ftp_response_tmp_local_path."/$filename");
		$this->analyse();
	}
	
	public function testAnalysePesRetourNonAbonne(){
		$this->expectOutputRegex("#La collectivité 66920145100015 n'est pas abonnée à l'application Comptabilité Publique du TdT#");
		$this->analysePesRetour(__DIR__."/fixtures/pes_retour_nonabonne.xml");
		$this->assertFalse(file_exists($this->helios_response_root."/pes_retour_nonabonne.xml"));
	}
	
	public function testAnalyseDeplacementErreurImpossible(){
		copy(__DIR__."/fixtures/pes_retour_nonabonne.xml", $this->helios_responses_error_path."/pes_retour_nonabonne.xml");
		$this->expectOutputRegex("#Impossible de déplacer le fichier pes_retour_nonabonne.xml dans le répertoire des fichiers en erreur : le fichier existe déjà#");
		$this->analysePesRetour(__DIR__."/fixtures/pes_retour_nonabonne.xml");
		$this->assertFalse(file_exists($this->helios_response_root."/pes_retour_nonabonne.xml"));
	}

	public function testAnalysePesRetourDoubleSire(){
		$authoritySireSQL = new AuthoritySiretSQL($this->getSQLQuery());
		$authoritySireSQL->add(1,"12345678900035");
		$authoritySireSQL->add(2,"12345678900035");
		$this->expectOutputRegex('#Le SIRET 12345678900035 est associé à plusieurs collectivités#');
		$this->analysePesRetour(__DIR__."/fixtures/pes_retour.xml");
		$this->assertFalse(file_exists($this->helios_response_root."/pes_retour.xml"));
	}

	public function testOcre(){
		$filename = "toto.ocre";
		file_put_contents($this->helios_ftp_response_tmp_local_path."/$filename","test");
		$this->expectOutputRegex("#Traitement de vfs://test/helios_ftp_response_tmp_local_path/toto.ocre#");
		$this->analyse();
		$this->assertTrue(file_exists($this->helios_ocre."/".$filename));
	}


}