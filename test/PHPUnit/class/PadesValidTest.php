<?php

class PadesValidTest extends S2lowTestCase {

    /** @var  PadesValid */
    private $padesValid;

    protected function setUp() : void {

    }

//    private function getCurlWrapperFactory($return_string){
//        $curlWrapper = $this->getMockBuilder("CurlWrapper")->getMock();
//        $curlWrapper->method("get")->willReturn($return_string);
//
//        $curlWrapperFactory = $this->getMockBuilder("CurlWrapperFactory")->getMock();
//        $curlWrapperFactory->method("getNewInstance")->willReturn($curlWrapper);
//        /** @var CurlWrapperFactory $curlWrapperFactory */
//        return $curlWrapperFactory;
//    }
//
//    private function createPadeValid($checkCertificateThrowAnException = false){
//        $rgs_validca_path = __DIR__ . "/../lib/fixtures/validca/";
//        $this->padesValid = new PadesValid("", $rgs_validca_path);
//		$verifyPadesSignature = new VerifyPadesSignature($this->getVerifyPemCertificateMock($checkCertificateThrowAnException));
//		$this->padesValid->setVerifyPadesSignature($verifyPadesSignature);
//	}
//
//    public function getVerifyPemCertificateMock($checkCertificateThrowAnException = false){
//        $verifyPemCertificateMock = $this->getMockBuilder(VerifyPemCertificate::class)->disableOriginalConstructor()->getMock();
//
//		if ($checkCertificateThrowAnException) {
//			$verifyPemCertificateMock->method("checkCertificateWithoutCheckingCertificateChain")->willThrowException(new Exception("problème"));
//		} else {
//			$verifyPemCertificateMock->method("checkCertificateWithoutCheckingCertificateChain")->willReturn(true);
//		}
//
//		/** @var VerifyPemCertificate $verifyPemCertificateMock */
//        return $verifyPemCertificateMock;
//    }

    /**
     * @throws Exception
     */
    public function testValidateNotSigned(){

        $returnString = '{"signatures":[],"signed":false}';
    	$padesValid = new PadesValid("bli","bla");

    	$curlWrapperMock = $this->getMockBuilder(CurlWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

    	$curlWrapperMock->method("get")
            ->willReturn($returnString);

    	$curlWrapperFactoryMock = $this->getMockBuilder(CurlWrapperFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
    	$curlWrapperFactoryMock->method("getNewInstance")
            ->willReturn($curlWrapperMock);

    	$padesValid->setCurlWrapperFactory($curlWrapperFactoryMock);

    	$verifyPadesSignatureMock = $this->getMockBuilder(VerifyPadesSignature::class)
            ->disableOriginalConstructor()
            ->getMock();

    	$padesValid->setVerifyPadesSignature($verifyPadesSignatureMock);

        $this->assertFalse(
            $padesValid->validate(__DIR__."/fixtures/signature-pades/Courrier.pdf")
        );
    }

    public function testEmptyResultaAndLastHttpCode(){

        $returnString = '';
        $padesValid = new PadesValid("bli","bla");

        $curlWrapperMock = $this->getMockBuilder(CurlWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $lastError = "last error";
        $lastOutput = "last output";

        $curlWrapperMock->method("get")
            ->willReturn($returnString);
        $curlWrapperMock->method("getLastHttpCode")
            ->willReturn("404");
        $curlWrapperMock->method("getLastError")
            ->willReturn($lastError);
        $curlWrapperMock->method("getLastOutput")
            ->willReturn($lastOutput);

        $curlWrapperFactoryMock = $this->getMockBuilder(CurlWrapperFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $curlWrapperFactoryMock->method("getNewInstance")
            ->willReturn($curlWrapperMock);

        $padesValid->setCurlWrapperFactory($curlWrapperFactoryMock);

        $verifyPadesSignatureMock = $this->getMockBuilder(VerifyPadesSignature::class)
            ->disableOriginalConstructor()
            ->getMock();

        $padesValid->setVerifyPadesSignature($verifyPadesSignatureMock);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage($lastError." ".$lastOutput);
        $padesValid->validate(__DIR__."/fixtures/signature-pades/Courrier.pdf");
    }

    public function testEmptyResultaAndNoLastHttpCode(){

        $returnString = '';
        $padesValid = new PadesValid("bli","bla");

        $curlWrapperMock = $this->getMockBuilder(CurlWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $lastError = "last error";
        $lastOutput = "last output";

        $curlWrapperMock->method("get")
            ->willReturn($returnString);
        $curlWrapperMock->method("getLastHttpCode")
            ->willReturn("");
        $curlWrapperMock->method("getLastError")
            ->willReturn($lastError);
        $curlWrapperMock->method("getLastOutput")
            ->willReturn($lastOutput);

        $curlWrapperFactoryMock = $this->getMockBuilder(CurlWrapperFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $curlWrapperFactoryMock->method("getNewInstance")
            ->willReturn($curlWrapperMock);

        $padesValid->setCurlWrapperFactory($curlWrapperFactoryMock);

        $verifyPadesSignatureMock = $this->getMockBuilder(VerifyPadesSignature::class)
            ->disableOriginalConstructor()
            ->getMock();

        $padesValid->setVerifyPadesSignature($verifyPadesSignatureMock);

        $this->expectException(RecoverableException::class);
        $this->expectExceptionMessage($lastError." ".$lastOutput);
        $padesValid->validate(__DIR__."/fixtures/signature-pades/Courrier.pdf");
    }

    public function testRubbishCurlOutput(){

        $returnString = 'yiftfh';
        $padesValid = new PadesValid("bli","bla");

        $curlWrapperMock = $this->getMockBuilder(CurlWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $lastError = "last error";
        $lastOutput = "last output";

        $curlWrapperMock->method("get")
            ->willReturn($returnString);

        $curlWrapperFactoryMock = $this->getMockBuilder(CurlWrapperFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $curlWrapperFactoryMock->method("getNewInstance")
            ->willReturn($curlWrapperMock);

        $padesValid->setCurlWrapperFactory($curlWrapperFactoryMock);

        $verifyPadesSignatureMock = $this->getMockBuilder(VerifyPadesSignature::class)
            ->disableOriginalConstructor()
            ->getMock();

        $padesValid->setVerifyPadesSignature($verifyPadesSignatureMock);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Impossible de décoder le message de pades-valid : ");
        $padesValid->validate(__DIR__."/fixtures/signature-pades/Courrier.pdf");
    }

    public function testJsonWithoutSignedField(){
        $returnString = '{"signatures":[]}';
        $padesValid = new PadesValid("bli","bla");

        $curlWrapperMock = $this->getMockBuilder(CurlWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $lastError = "last error";
        $lastOutput = "last output";

        $curlWrapperMock->method("get")
            ->willReturn($returnString);

        $curlWrapperFactoryMock = $this->getMockBuilder(CurlWrapperFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $curlWrapperFactoryMock->method("getNewInstance")
            ->willReturn($curlWrapperMock);

        $padesValid->setCurlWrapperFactory($curlWrapperFactoryMock);

        $verifyPadesSignatureMock = $this->getMockBuilder(VerifyPadesSignature::class)
            ->disableOriginalConstructor()
            ->getMock();

        $padesValid->setVerifyPadesSignature($verifyPadesSignatureMock);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Impossible de determiner si le fichier est signé");
        $padesValid->validate(__DIR__."/fixtures/signature-pades/Courrier.pdf");
    }

    public function testEmptySignaturesArray(){
        $returnString = '{"signatures":[],"signed":true}';
        $padesValid = new PadesValid("bli","bla");

        $curlWrapperMock = $this->getMockBuilder(CurlWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $lastError = "last error";
        $lastOutput = "last output";

        $curlWrapperMock->method("get")
            ->willReturn($returnString);

        $curlWrapperFactoryMock = $this->getMockBuilder(CurlWrapperFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $curlWrapperFactoryMock->method("getNewInstance")
            ->willReturn($curlWrapperMock);

        $padesValid->setCurlWrapperFactory($curlWrapperFactoryMock);

        $verifyPadesSignatureMock = $this->getMockBuilder(VerifyPadesSignature::class)
            ->disableOriginalConstructor()
            ->getMock();

        $padesValid->setVerifyPadesSignature($verifyPadesSignatureMock);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Impossible de determiner si le fichier est signé");
        $padesValid->validate(__DIR__."/fixtures/signature-pades/Courrier.pdf");
    }

}