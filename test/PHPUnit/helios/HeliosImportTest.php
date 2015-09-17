<?php

class HeliosImportTest extends S2lowTestCase {



	/**
	 * @preserveGlobalState disabled
	 * @runInSeparateProcess
	 */
	public function testDoStuff(){
		org\bovigo\vfs\vfsStream::setup("test");

		$testStreamUrl = org\bovigo\vfs\vfsStream::url("test");

		$tmp_file = $testStreamUrl."/pes_aller.xml";
		file_put_contents($tmp_file,file_get_contents(__DIR__."/fixtures/pes_aller.xml"));

		$_FILES['enveloppe'] = array('name'=>'pes_aller.xml','tmp_name'=>$tmp_file);

		$this->setUserAuthentification();
		$heliosImport = new HeliosImport();
		//$heliosImport->doStuff();

	}

}
