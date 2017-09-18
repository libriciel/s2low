<?php

class ActeTamponneTest extends PHPUnit_Framework_TestCase {

	public function testGetTampon(){


		$actesTransactionsSQL = $this->getMockBuilder("ActesTransactionsSQL")->disableOriginalConstructor()->getMock();

		$transactionInfo = array('submission_date'=>'2016-12-12','date'=>'toto','unique_id'=>'hhhh','flux_retour'=>'<toto></toto>');

		$actesTransactionsSQL->expects($this->any())->method('getInfo')->willReturn($transactionInfo);
		/** @var  ActesTransactionsSQL $actesTransactionsSQL */


		$acteTamponne = new ActeTamponne($actesTransactionsSQL,new PDFStampWrapper(""));

		$acteTamponne->tamponnerPDF(__DIR__."/../fixtures/vide.pdf","12");

	}

}