<?php

class PDFStampWrapperTest extends PHPUnit_Framework_TestCase {

    private function getCurlWrapper($return_string){
        $curlWrapper = $this->getMockBuilder("CurlWrapper")->getMock();
        $curlWrapper->expects($this->any())->method("get")->willReturn($return_string);
        /** @var CurlWrapper $curlWrapper */
        return $curlWrapper;
    }

    public function testStamp(){
        $pdfStampWrapper = new PDFStampWrapper("http://pdf-stamp/");
        $pdfStampWrapper->setCurlWrapper($this->getCurlWrapper("test"));
        $pdfStampData = new PDFStampData();
        $pdfStampData->identifiant_unique = "toto";
        $pdfStampData->affichage_date = "2017-09-18";
        $pdfStampData->envoi_prefecture_date = "2017-09-15";
        $pdfStampData->recu_prefecture_date = "2017-09-16";

        $this->assertEquals(
            "test",
            $pdfStampWrapper->stamp(__DIR__."/fixtures/signature-pades/Courrier.pdf",$pdfStampData)
        );

    }
}