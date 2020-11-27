<?php

class PadesValidTest extends S2lowTestCase {

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
     * @param string $returnString
     * @param string $lastHttpCode
     * @param string $lastError
     * @param string $lastOutput
     * @return PadesValid
     */

    private function createPadesValidForExceptions(string $returnString, string $lastHttpCode, string $lastError, string $lastOutput)
    {
        $padesValid = new PadesValid("bli","bla");

        $curlWrapperMock = $this->getMockBuilder(CurlWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $curlWrapperMock->method("get")
            ->willReturn($returnString);
        $curlWrapperMock->method("getLastHttpCode")
            ->willReturn($lastHttpCode);
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

        return $padesValid;
    }

    private function createPadesValidForValidation(
        array $callRepartition = [1,0],
        string $exceptionMessage = null,
        string $returnString = '{"signatures":["une signature"],"signed":true}'
    )
    {
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

        if(! is_null($exceptionMessage)){
            echo "exception";
            $verifyPadesSignatureMock->expects(
                $this->exactly($callRepartition[0])
            )->method('validateSignature')->willThrowException(
                new Exception($exceptionMessage)
            );
            $verifyPadesSignatureMock->expects(
                $this->exactly($callRepartition[1])
            )->method('validateSignatureWithoutCertificateChecking')->willThrowException(
                new Exception($exceptionMessage)
            );
        } else {
            echo "no Exception";
            $verifyPadesSignatureMock->expects(
                $this->exactly($callRepartition[0])
            )->method('validateSignature');
            $verifyPadesSignatureMock->expects(
                $this->exactly($callRepartition[1])
            )->method('validateSignatureWithoutCertificateChecking');
        }

        $padesValid->setVerifyPadesSignature($verifyPadesSignatureMock);

        return $padesValid;
    }

    /**
     * @throws Exception
     */
    public function testValidateNotSigned(){

        $returnString = '{"signatures":[],"signed":false}';

        $lastError = "";
        $lastOutput = "";
        $lastHttpCode = "";

        $padesValid = $this->createPadesValidForExceptions($returnString, $lastHttpCode, $lastError, $lastOutput);

        $verifyPadesSignatureMock = $this->getMockBuilder(VerifyPadesSignature::class)
            ->disableOriginalConstructor()
            ->getMock();

        $padesValid->setVerifyPadesSignature($verifyPadesSignatureMock);

        $this->assertFalse(
            $padesValid->validate(__DIR__."/fixtures/signature-pades/Courrier.pdf")
        );
    }

    /**
     * @dataProvider provider
     * @throws RecoverableException
     */
    public function testgetPadesValidResultExceptions(
                            $returnString,
                            $lastError,
                            $lastOutput,
                            $lastHttpCode,
                            $exceptionClass,
                            $exceptionMessage
    ){

        $padesValid = $this->createPadesValidForExceptions(
            $returnString,
            $lastHttpCode,
            $lastError,
            $lastOutput
        );

        $this->expectException($exceptionClass);
        $this->expectExceptionMessage($exceptionMessage);
        $padesValid->validate(__DIR__."/fixtures/signature-pades/Courrier.pdf");
    }

    public function provider(){
        return[
            ['{"signatures":[],"signed":true}',"","","",Exception::class,"Impossible de determiner si le fichier est signé"],
            ['{"signatures":[]}',"","","",Exception::class,"Impossible de determiner si le fichier est signé"],
            ['',"last error","last output","404",Exception::class,"last error last output"],
            ['',"last error","last output","",RecoverableException::class,"last error last output"],
            ["uzye","","","",Exception::class,"Impossible de décoder le message de pades-valid : "],


        ];
    }

    /**
     * @throws RecoverableException
     */
    public function testvalidate(){
        $padesValid = $this->createPadesValidForValidation();

        $this->assertTrue(
            $padesValid->validate("/vers/un/fichier")
        );
    }

    public function testValidateCertificateChecking(){
        $padesValid = $this->createPadesValidForValidation();

        $this->assertTrue(
            $padesValid->validate("/vers/un/fichier",true)
        );
    }

    public function testWithoutCertificateChecking(){
        $padesValid = $this->createPadesValidForValidation([0,1]);

        $this->assertTrue(
            $padesValid->validate("/vers/un/fichier",false)
        );
    }

    public function testExceptionThrowGetsThrough(){
        $padesValid = $this->createPadesValidForValidation(
            [1,0],
            "Une Exception"
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Une Exception");

        $padesValid->validate("/vers/un/fichier");
    }

    //TODO : tester
    // 1) le paramètre certificate Checking
    // 2) que les Exceptions passent bien ...

}