<?php

class PadesValidTest extends S2lowTestCase {

    /**
     * @param string $returnString
     * @param string $lastHttpCode
     * @param string $lastError
     * @param string $lastOutput
     * @return PadesValid
     */

    private function createPadesValidForExceptions(string $returnString, string $lastHttpCode, string $lastError, string $lastOutput)
    {
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

        $verifyPadesSignatureMock = $this->getMockBuilder(VerifyPadesSignature::class)
            ->disableOriginalConstructor()
            ->getMock();

        $padesValid = new PadesValid("bli",$curlWrapperFactoryMock,$verifyPadesSignatureMock);

        return $padesValid;
    }

    private function createPadesValidForValidation(
        array $callRepartition = [1,0],
        string $exceptionMessage = null,
        string $returnString = '{"signatures":["une signature"],"signed":true}'
    )
    {

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

        $verifyPadesSignatureMock = $this->getMockBuilder(VerifyPadesSignature::class)
            ->disableOriginalConstructor()
            ->getMock();

        if(! is_null($exceptionMessage)){
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
            $verifyPadesSignatureMock->expects(
                $this->exactly($callRepartition[0])
            )->method('validateSignature');
            $verifyPadesSignatureMock->expects(
                $this->exactly($callRepartition[1])
            )->method('validateSignatureWithoutCertificateChecking');
        }

        $padesValid = new PadesValid("bli",$curlWrapperFactoryMock,$verifyPadesSignatureMock);

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
}