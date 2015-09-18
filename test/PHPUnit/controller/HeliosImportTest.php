<?php

class HeliosImportTest extends S2lowTestCase {

	/**
	 * @preserveGlobalState disabled
	 * @runInSeparateProcess
	 */
	public function testImportAction(){
		org\bovigo\vfs\vfsStream::setup("test");
		$testStreamUrl = org\bovigo\vfs\vfsStream::url("test");

		mkdir($testStreamUrl."/helios");

		$tmp_file = $testStreamUrl."/pes_aller.xml";
		file_put_contents($tmp_file,file_get_contents(__DIR__."/fixtures/pes_aller.xml"));

		$_FILES['enveloppe'] = array('name'=>'pes_aller.xml','tmp_name'=>$tmp_file,'size'=>filesize($tmp_file));

		$this->setUserAuthentification();
		$heliosController = new HeliosController($this->getObjectInstancier());
		$this->setExpectedException("Exception");
		$heliosController->importAction();

	}

	/**
	 * @preserveGlobalState disabled
	 * @runInSeparateProcess
	 */
	public function testImportAPIAction(){
		org\bovigo\vfs\vfsStream::setup("test");
		$testStreamUrl = org\bovigo\vfs\vfsStream::url("test");

		mkdir($testStreamUrl."/helios");

		$tmp_file = $testStreamUrl."/pes_aller.xml";
		file_put_contents($tmp_file,file_get_contents(__DIR__."/fixtures/pes_aller.xml"));

		$_FILES['enveloppe'] = array('name'=>'pes_aller.xml','tmp_name'=>$tmp_file,'size'=>filesize($tmp_file));

		$this->setUserAuthentification();
		$heliosController = new HeliosController($this->getObjectInstancier());
		$this->expectOutputRegex("#<resultat>OK</resultat>#");
		$heliosController->importAPIAction();

	}

}
