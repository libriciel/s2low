<?php

class AntivirusTest extends S2lowTestCase {

	/**
	 * @throws Exception
	 */
	public function testOK(){
		$antivirus = $this->getObjectInstancier()->get(Antivirus::class);
		$this->assertTrue($antivirus->checkArchiveSanity(__DIR__."/fixtures/classification.xml"));
	}

	/**
	 * @throws Exception
	 */
	public function testKO(){
		$antivirus = $this->getObjectInstancier()->get(Antivirus::class);
		$this->assertFalse($antivirus->checkArchiveSanity(__DIR__."/fixtures/no_existing"));
	}

}