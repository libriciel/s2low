<?php

class ActeTamponneTest extends S2lowTestCase {

	public function testGetTampon(){
		$actesTransactionsSQL = $this->getMockBuilder("ActesTransactionsSQL")->disableOriginalConstructor()->getMock();
		$transactionInfo = array('submission_date'=>'2016-12-12','date'=>'toto','unique_id'=>'hhhh','flux_retour'=>'<toto></toto>');
		$actesTransactionsSQL->method('getInfo')->willReturn($transactionInfo);

		/** @var  ActesTransactionsSQL $actesTransactionsSQL */
		$acteTamponne = new ActeTamponne($actesTransactionsSQL,new PDFStampWrapper("","/public.ssl/custom/images/s2low-stamp.png"), $this->getObjectInstancier()->get(S2lowLogger::class));

		$acteTamponne->tamponnerPDF(__DIR__."/../fixtures/vide.pdf","12");
		$this->assertEquals("Impossible de tamponné l'acte 12 : Erreur de connexion au serveur : <url> malformed ",
			$this->getLogRecords()[0]['message']
		);
	}

}
