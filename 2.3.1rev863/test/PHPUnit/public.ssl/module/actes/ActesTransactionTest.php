<?php

require_once(SITEROOT."/public.ssl/modules/actes/class/ActesTransaction.class.php");


class ActesTransactionTest extends S2lowTestCase {

	public function testSetNumber(){
		$this->numberTest("AXY_123",true);
	}

	private function numberTest($number,$valide){
		$actesTransaction = new ActesTransaction();
		$actesTransaction->set('number',$number);
		$actesTransaction->validate();
		$error_msg = $actesTransaction->getErrorMsg();
		$number_error = "Le champ Numéro de l'acte ne peut contenir que des chiffres, des lettres en majuscules et _";
		if ($valide){
			$this->assertNotContains($number_error, $error_msg);
		} else {
			$this->assertContains($number_error, $error_msg);
		}
	}

	public function testSetNumberIncorrect(){
		$this->numberTest("foo",false);
	}

	public function testBugNumber(){
		$this->numberTest("_123_AXY",true);
	}

}