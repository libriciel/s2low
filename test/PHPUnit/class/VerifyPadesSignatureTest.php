<?php

// VerifyPadesSignature::validateSignature et VerifyPadesSignature::validateSignature :
// - renvoient void si ok
// - throw une exception sinon

class VerifyPadesSignatureTest extends S2lowTestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\VerifyPemCertificate
     */
    private $verifyPemCertificateMock;
    /** @var \VerifyPadesSignature | PHPUnit\Framework\MockObject\  */
    private $verifyPadesSignatureWithMock;
    /** @var \VerifyPadesSignature  */
    private $verifyPadesSignature;
    /**
     * @var \PemCertificate|\PHPUnit\Framework\MockObject\MockObject
     */
    private $pemCertificateMock;

    private function getSignature(
        bool $valid=true,
        string $signingCert="certificat",
        string $signatureDate="1502268600000"
    ){
        $signature = $this->getMockBuilder(stdClass::class)
            ->disableOriginalConstructor()
            ->getMock();

        $signature->valid = $valid;
        $signature->signingCert = $signingCert;
        $signature->signatureDate =$signatureDate;

        return $signature;
    }

    protected function setUp() :void {
        $this->verifyPemCertificateMock = $this->getMockBuilder(VerifyPemCertificate::class)
            ->disableOriginalConstructor()
            ->getMock();
        /** @var  $verifyPemCertificateFactoryMock \PHPUnit\Framework\MockObject\MockObject | \VerifyPemCertificateFactory */
        $verifyPemCertificateFactoryMock = $this->getMockBuilder(VerifyPemCertificateFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $verifyPemCertificateFactoryMock->method('get')->willReturn($this->verifyPemCertificateMock);

        $this->pemCertificateMock = $this->getMockBuilder(PemCertificate::class)
            ->disableOriginalConstructor()
            ->getMock();    
            
        $pemCertificateFactoryMock = $this->getMockBuilder(PemCertificateFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $pemCertificateFactoryMock->method('getFromMinimalString')
            ->willReturn($this->pemCertificateMock);

        $this->verifyPadesSignatureWithMock = new VerifyPadesSignature(
            "pathToValidCA",
            $verifyPemCertificateFactoryMock,
            $pemCertificateFactoryMock
        );

        $verifyPemCertificateFactory = new VerifyPemCertificateFactory();

        $this->verifyPadesSignature = new VerifyPadesSignature(
            __DIR__."/../lib/fixtures/validca/",
            $verifyPemCertificateFactory,
            new PemCertificateFactory()
        );

    }

    //Integration tests

    /**
     * @throws \Exception
     */
    public function testValidateSigned(){
        $signature = json_decode(
            file_get_contents(__DIR__."/fixtures/signature-pades/return-courrier-signe.json")
        )->signatures[0];

        $this->expectNotToPerformAssertions();
        $this->verifyPadesSignature->validateSignature($signature);
    }

    /**
     * @throws Exception
     */
    public function testNotValidateSigned(){
        $signature = json_decode(
            file_get_contents(__DIR__."/fixtures/signature-pades/return-courrier-alter.json")
        )->signatures[0];
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Au moins une signature n'est pas valide");
        $this->verifyPadesSignature->validateSignature($signature);
    }

    /**
     * @throws Exception
     */
    public function testValidateSignedNoCertificatCheking(){
        $signature = json_decode(
            file_get_contents(__DIR__."/fixtures/signature-pades/return-courrier-signe.json")
        )->signatures[0];

        $this->expectNotToPerformAssertions();
        $this->verifyPadesSignature->validateSignatureWithoutCertificateChecking($signature);
    }

    /**
     * @throws Exception
     */
    public function testNotValidateAlteredSignature(){
        $signature = json_decode(
            file_get_contents(__DIR__."/fixtures/signature-pades/return-courrier-alter.json")
        )->signatures[0];
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Au moins une signature n'est pas valide");
        $this->verifyPadesSignature->validateSignatureWithoutCertificateChecking($signature);
    }

    //Unit tests
    // checkNecessaryFields

    /**
     * @dataProvider missingNecessaryFieldsProvider
     */

    public function testMissingNecessaryFields($signature, $exceptionMessage){

        $this->expectException(Exception::class);
        $this->expectExceptionMessage($exceptionMessage);
        $this->verifyPadesSignatureWithMock->validateSignature($signature);
    }

    public function missingNecessaryFieldsProvider(){
        return [
            [
                $this->getSignature(false,"",""),
                "Au moins une signature n'est pas valide"
            ],
            [
                $this->getSignature(true,"",""),
                "Impossible de récupérer le certificat de signature"
            ],
            [
                $this->getSignature(true,"certificat",""),
                "Impossible de determiner la date de la signature"
            ]
        ];

    }

    // checkCertificateWasValidAtSignatureTime

    public function testCertificateWasValidOnSignature(){
        $this->expectNotToPerformAssertions();
        $this->verifyPadesSignatureWithMock->validateSignature($this->getSignature());
    }

    /**
     * @throws Exception
     */
    public function testCertificateWasInvalidOnSignature(){
        $date = new DateTime();
        $date->setTimestamp(1502268600);
        $this->pemCertificateMock
            ->expects($this->once())
            ->method('checkCertificateIsValidAtDate')
            ->with($date);

        $this->verifyPadesSignatureWithMock->validateSignatureWithoutCertificateChecking($this->getSignature());
    }

    /**
     * @throws Exception
     */
    public function testCertificateDateInvalidGoesThrough(){
        $this->pemCertificateMock
            ->method('checkCertificateIsValidAtDate')
            ->willThrowException(new Exception("CkSugdE3ETSh9xhQ"));
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("CkSugdE3ETSh9xhQ");
        $this->verifyPadesSignatureWithMock->validateSignatureWithoutCertificateChecking($this->getSignature());
    }

    // Check checkCertificateWithoutCheckingCertificateChain est
    //  - appelé par validateSignature
    //  - pas appelé par validateSignatureWithoutCertificateChecking

    public function testcheckCertificateWithoutCheckingCertificateChainIsCalled(){
        $this->verifyPemCertificateMock
            ->expects($this->once())
            ->method("checkCertificateWithOpenSSL")
            ->with($this->stringContains(
                "/s2low_valid_certifcate_"),
                $this->equalTo(VerifyPemCertificate::CERTIFICATE_CHAIN_ERRORS)
            );

        $this->verifyPadesSignatureWithMock->validateSignature($this->getSignature());
    }


    public function testCheckCertificateWithOpenSSLIsNotCalled(){

        $this->verifyPemCertificateMock
            ->expects($this->never())
            ->method("checkCertificateWithOpenSSL");

        $this->verifyPadesSignatureWithMock->validateSignatureWithoutCertificateChecking($this->getSignature());
    }

    // Test que l'exception lancée par checkCertificateWithoutCheckingCertificateChain passe le cas échéant

    public function testcheckCertificateWithoutCheckingCertificateChainExceptionGoesThrough(){
        $this->verifyPemCertificateMock
            ->method("checkCertificateWithOpenSSL")
            ->willThrowException(new Exception("Exception de test LahgnjCM"));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Exception de test LahgnjCM");
        $this->verifyPadesSignatureWithMock->validateSignature($this->getSignature());
    }
}