<?php

require_once(__DIR__."/../init.php");

class HeliosRetourSQLTest  extends S2lowTestCase {

	public function testCreate(){
		$heliosRetourSQL = new HeliosRetourSQL($this->getSQLQuery());
		$heliosRetourSQL->create("123456789", "toto.txt");
		
		
	}
}