<?php

class PadesValidTest extends S2lowTestCase {

    /** @var  PadesValid */
    private $padesValid;

    protected function setUp(){
        $this->padesValid = new PadesValid("",__DIR__."/../lib/fixtures/validca/");
        $this->padesValid->setVerifyPKCS7Signature($this->getPKCS7Signature());
    }

    private function getCurlWrapper($return_string){
        $curlWrapper = $this->getMockBuilder("CurlWrapper")->getMock();
        $curlWrapper->expects($this->any())->method("get")->willReturn($return_string);
        /** @var CurlWrapper $curlWrapper */
        return $curlWrapper;
    }

    public function getPKCS7Signature(){
        $verifyPKCS7SIgnature = $this->getMockBuilder('VerifyPKCS7SIgnature')->disableOriginalConstructor()->getMock();
        $verifyPKCS7SIgnature->expects($this->any())->method("checkCertificate")->willReturn(true);
        /** @var VerifyPKCS7SIgnature $verifyPKCS7SIgnature */
        return $verifyPKCS7SIgnature;
    }

    public function testValidateNotSigned(){
        $this->padesValid->setCurlWrapper($this->getCurlWrapper('{"signatures":[],"signed":false}'));
        $this->assertFalse($this->padesValid->validate(__DIR__."/fixtures/signature-pades/Courrier.pdf"));
    }

    public function testValidateSigned(){
        $this->padesValid->setCurlWrapper($this->getCurlWrapper(
            file_get_contents(__DIR__."/fixtures/signature-pades/return-courrier-signe.json"))
        );
        $this->assertTrue($this->padesValid->validate(__DIR__."/fixtures/signature-pades/Courrier_signe.pdf"));
    }

    public function testNotValidateSigned(){
        $this->padesValid->setCurlWrapper($this->getCurlWrapper(
            file_get_contents(__DIR__."/fixtures/signature-pades/return-courrier-alter.json"))
        );
        $this->setExpectedException("Exception","Au moins une signature n'est pas valide");
        $this->padesValid->validate(__DIR__."/fixtures/signature-pades/Courrier_alter.pdf");
    }


}