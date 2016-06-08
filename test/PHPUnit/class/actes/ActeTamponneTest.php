<?php

class ActeTamponneTest extends PHPUnit_Framework_TestCase {

	public function testGetTampon(){


		$actesTransactionsSQL = $this->getMockBuilder("ActesTransactionsSQL")->disableOriginalConstructor()->getMock();

		$transactionInfo = array('submission_date'=>'2016-12-12','date'=>'toto','unique_id'=>'hhhh');

		$actesTransactionsSQL->expects($this->any())->method('getInfo')->willReturn($transactionInfo);
		/** @var  ActesTransactionsSQL $actesTransactionsSQL */


		$acteTamponne = new ActeTamponne($actesTransactionsSQL);

		$tampon = $acteTamponne->tamponnerPDF(__DIR__."/../fixtures/vide.pdf","12");
		
		file_put_contents("/Users/eric/Desktop/test.pdf",$tampon);
		
	}

}