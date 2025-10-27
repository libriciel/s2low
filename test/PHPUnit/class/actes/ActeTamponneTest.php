<?php

use Monolog\Level;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\actes\ActeTamponne;
use S2lowLegacy\Class\PdfStampMessages;
use S2lowLegacy\Class\PDFStampWrapper;

class ActeTamponneTest extends S2lowTestCase
{
    public function testGetTampon()
    {
        $actesTransactionsSQL = $this->getMockBuilder(ActesTransactionsSQL::class)->disableOriginalConstructor()->getMock();
        $transactionInfo = array('submission_date' => '2016-12-12','date' => 'toto','unique_id' => 'hhhh','flux_retour' => '<toto></toto>');
        $actesTransactionsSQL->method('getInfo')->willReturn($transactionInfo);
        $actesTransactionsSQL->method('getDateTampon')->willReturn($transactionInfo);
        $actesTransactionsSQL->method('getStatusInfoWithFluxRetour')->willReturn(['flux_retour' => "<test></test>"]);

        /** @var  ActesTransactionsSQL $actesTransactionsSQL */
        $acteTamponne = new ActeTamponne(
            $actesTransactionsSQL,
            new PDFStampWrapper(
                "",
                __DIR__ . "/../../../../public.ssl/custom/images/s2low-stamp.png",
                self::getContainer()->get(PdfStampMessages::class)
            ),
            $this->s2lowLogger
        );

        $acteTamponne->tamponnerPDF(__DIR__ . "/../fixtures/vide.pdf", "12");
        self::assertTrue(
            $this->testHandler->hasRecord(
                "Impossible de tamponné l'acte 12 :  ",
                Level::Error
            )
        );
    }
}
