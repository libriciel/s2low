<?php

class PDFStampWrapperTest extends PHPUnit_Framework_TestCase {

    private function getCurlWrapperFactory($return_string){
        $curlWrapper = $this->getMockBuilder("CurlWrapper")->getMock();
        $curlWrapper->method("get")->willReturn($return_string);
        $curlWrapperFactory = $this->getMockBuilder("CurlWrapperFactory")->getMock();
        $curlWrapperFactory->method("getNewInstance")->willReturn($curlWrapper);
        /** @var CurlWrapperFactory $curlWrapperFactory */
        return $curlWrapperFactory;
    }

    /**
     * @throws Exception
     */
    public function testStamp(){
        $pdfStampWrapper = new PDFStampWrapper("http://pdf-stamp/","/var/www/s2low/public.ssl/custom/images/s2low-stamp.png");
        $pdfStampWrapper->setCurlWrapperFactory($this->getCurlWrapperFactory("test"));
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
