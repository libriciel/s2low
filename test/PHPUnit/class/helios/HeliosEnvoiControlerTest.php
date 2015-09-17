<?php


class HeliosEnvoiControlerTest extends S2lowTestCase {

	public function testValidateAllTransactions(){
		$heliosEnvoiControler = new HeliosEnvoiControler($this->getSQLQuery());
		$heliosEnvoiControler->validateAllTransactions();
	}


}
