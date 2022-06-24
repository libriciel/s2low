<?php

class ActeTamponneTest extends S2lowTestCase
{
    public function testGetTampon()
    {
        $actesTransactionsSQL = $this->getMockBuilder("ActesTransactionsSQL")->disableOriginalConstructor()->getMock();
        $transactionInfo = array('submission_date' => '2016-12-12','date' => 'toto','unique_id' => 'hhhh','flux_retour' => '<toto></toto>');
        $actesTransactionsSQL->method('getInfo')->willReturn($transactionInfo);
        $actesTransactionsSQL->method('getDateTampon')->willReturn($transactionInfo);
        $actesTransactionsSQL->method('getStatusInfo')->willReturn(['flux_retour' => "<test></test>"]);

        /** @var  ActesTransactionsSQL $actesTransactionsSQL */
        $acteTamponne = new ActeTamponne($actesTransactionsSQL, new PDFStampWrapper("", __DIR__ . "/../../../../public.ssl/custom/images/s2low-stamp.png"), $this->getObjectInstancier()->get(S2lowLogger::class));

        $acteTamponne->tamponnerPDF(__DIR__ . "/../fixtures/vide.pdf", "12");
        $this->assertEquals(
            "Impossible de tamponné l'acte 12 :  ", // Supprime "Erreur de connexion au serveur : <url> malformed " Chgt de comportement de curl ??
            $this->getLogRecords()[0]['message']
        );
    }
}
