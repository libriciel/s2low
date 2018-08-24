<?php

class PadesValidTest extends S2lowTestCase {

    /** @var  PadesValid */
    private $padesValid;

    protected function setUp(){

    }

    private function getCurlWrapperFactory($return_string){
        $curlWrapper = $this->getMockBuilder("CurlWrapper")->getMock();
        $curlWrapper->expects($this->any())->method("get")->willReturn($return_string);

        $curlWrapperFactory = $this->getMockBuilder("CurlWrapperFactory")->getMock();
        $curlWrapperFactory->expects($this->any())->method("getNewInstance")->willReturn($curlWrapper);
        /** @var CurlWrapperFactory $curlWrapperFactory */
        return $curlWrapperFactory;
    }

    private function createPadeValid($checkCertificateThrowAnException = false){
		$this->padesValid = new PadesValid("",__DIR__."/../lib/fixtures/validca/");
		$this->padesValid->setVerifyPKCS7Signature($this->getPKCS7Signature($checkCertificateThrowAnException));
	}

    public function getPKCS7Signature($checkCertificateThrowAnException = false){
        $verifyPKCS7Signature = $this->getMockBuilder('VerifyPKCS7Signature')->disableOriginalConstructor()->getMock();

		if ($checkCertificateThrowAnException) {
			$verifyPKCS7Signature->expects($this->any())->method("checkCertificate")->willThrowException(new Exception("problème"));
		} else {
			$verifyPKCS7Signature->expects($this->any())->method("checkCertificate")->willReturn(true);
		}

		/** @var VerifyPKCS7Signature $verifyPKCS7Signature */
        return $verifyPKCS7Signature;
    }

    /**
     * @throws Exception
     */
    public function testValidateNotSigned(){
    	$this->createPadeValid();
        $this->padesValid->setCurlWrapperFactory($this->getCurlWrapperFactory('{"signatures":[],"signed":false}'));
        $this->assertFalse($this->padesValid->validate(__DIR__."/fixtures/signature-pades/Courrier.pdf"));
    }

    /**
     * @throws Exception
     */
    public function testValidateSigned(){
		$this->createPadeValid();
        $this->padesValid->setCurlWrapperFactory($this->getCurlWrapperFactory(
            file_get_contents(__DIR__."/fixtures/signature-pades/return-courrier-signe.json"))
        );
        $this->assertTrue($this->padesValid->validate(__DIR__."/fixtures/signature-pades/Courrier_signe.pdf"));
    }

    /**
     * @throws Exception
     */
    public function testNotValidateSigned(){
		$this->createPadeValid();
        $this->padesValid->setCurlWrapperFactory($this->getCurlWrapperFactory(
            file_get_contents(__DIR__."/fixtures/signature-pades/return-courrier-alter.json"))
        );
        $this->setExpectedException("Exception","Au moins une signature n'est pas valide");
        $this->padesValid->validate(__DIR__."/fixtures/signature-pades/Courrier_alter.pdf");
    }

	/**
	 * @throws Exception
	 */
	public function testValidateSignedNoCertificatCheking(){
		$this->createPadeValid();
		$this->padesValid->setCurlWrapperFactory($this->getCurlWrapperFactory(
			file_get_contents(__DIR__."/fixtures/signature-pades/return-courrier-signe.json"))
		);
		$this->assertTrue($this->padesValid->validateWithoutCertificateChecking(__DIR__."/fixtures/signature-pades/Courrier_signe.pdf"));
	}

	/**
	 * @throws Exception
	 */
	public function testValidateSignedBadCertificate(){
		$this->createPadeValid(true);
		$this->padesValid->setCurlWrapperFactory($this->getCurlWrapperFactory(
			file_get_contents(__DIR__."/fixtures/signature-pades/return-courrier-signe.json"))
		);
		$this->setExpectedException("Exception","problème");
		$this->padesValid->validate(__DIR__."/fixtures/signature-pades/Courrier_signe.pdf");
	}

	/**
	 * @throws Exception
	 */
	public function testValidateSignedBadCertificateNoCheckCertificate(){
		$this->createPadeValid(false);
		$this->padesValid->setCurlWrapperFactory($this->getCurlWrapperFactory(
			file_get_contents(__DIR__."/fixtures/signature-pades/return-courrier-signe.json"))
		);
		$this->assertTrue(
			$this->padesValid->validate(__DIR__."/fixtures/signature-pades/Courrier_signe.pdf")
		);
	}

}