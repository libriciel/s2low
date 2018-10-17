<?php

require_once __DIR__."/../../../../../../public.ssl/modules/actes/class/ActesBatchFile.class.php";


class ActesBatchFileTest extends S2lowTestCase {

	/**
	 * @throws Exception
	 */
	public function testInit(){
		$actesBatchFile = new ActesBatchFile(1);
		$this->assertFalse($actesBatchFile->init());
	}

}