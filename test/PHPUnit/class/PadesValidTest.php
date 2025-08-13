<?php

use S2lowLegacy\Class\CurlWrapper;
use S2lowLegacy\Class\CurlWrapperFactory;
use S2lowLegacy\Class\PadesValid;
use S2lowLegacy\Class\RecoverableException;
use S2lowLegacy\Class\VerifyPadesSignature;

class PadesValidTest extends S2lowTestCase
{
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

        $padesValid = new PadesValid("bli", $curlWrapperFactoryMock, $verifyPadesSignatureMock);

        return $padesValid;
    }

    private function createPadesValidForValidation(
        bool $mustCheckInCertificateStore = true,
        string $exceptionMessage = null
    ) {
        $returnString = '{"signatures":["une signature"],"signed":true}';

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

        if (! is_null($exceptionMessage)) {
            $verifyPadesSignatureMock->expects(
                $this->exactly(1)
            )->method('validateSignature')->willThrowException(
                new Exception($exceptionMessage)
            )->with("une signature", $mustCheckInCertificateStore);
        } else {
            $verifyPadesSignatureMock->expects(
                $this->exactly(1)
            )->method('validateSignature')
                ->with("une signature", $mustCheckInCertificateStore);
        }

        $padesValid = new PadesValid("bli", $curlWrapperFactoryMock, $verifyPadesSignatureMock);

        return $padesValid;
    }

    /**
     * @throws Exception
     */
    public function testValidateNotSigned()
    {

        $returnString = '{"signatures":[],"signed":false}';

        $lastError = "";
        $lastOutput = "";
        $lastHttpCode = "";

        $padesValid = $this->createPadesValidForExceptions($returnString, $lastHttpCode, $lastError, $lastOutput);

        $this->assertFalse(
            $padesValid->validate(__DIR__ . "/fixtures/signature-pades/Courrier.pdf")
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
    ) {

        $padesValid = $this->createPadesValidForExceptions(
            $returnString,
            $lastHttpCode,
            $lastError,
            $lastOutput
        );

        $this->expectException($exceptionClass);
        $this->expectExceptionMessage($exceptionMessage);
        $padesValid->validate(__DIR__ . "/fixtures/signature-pades/Courrier.pdf");
    }

    public function provider()
    {
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
    public function testvalidate()
    {
        $padesValid = $this->createPadesValidForValidation();

        $this->assertTrue(
            $padesValid->validate("/vers/un/fichier")
        );
    }

    public function testValidateCertificateChecking()
    {
        $padesValid = $this->createPadesValidForValidation();

        $this->assertTrue(
            $padesValid->validate("/vers/un/fichier", true)
        );
    }

    public function testWithoutCertificateChecking()
    {
        $padesValid = $this->createPadesValidForValidation(false);

        $this->assertTrue(
            $padesValid->validate("/vers/un/fichier", false)
        );
    }

    public function testExceptionThrowGetsThrough()
    {
        $padesValid = $this->createPadesValidForValidation(
            true,
            "Une Exception"
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Une Exception");

        $padesValid->validate("/vers/un/fichier");
    }
}
