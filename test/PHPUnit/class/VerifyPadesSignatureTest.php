<?php

// VerifyPadesSignature::validateSignature et VerifyPadesSignature::validateSignature :
// - renvoient void si ok
// - throw une exception sinon

class VerifyPadesSignatureTest extends S2lowTestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\VerifyPemCertificate
     */
    private $verifyPemCertificate;
    /** @var \VerifyPadesSignature  */
    private $verifyPadesSignature;

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
        $this->verifyPemCertificate = $this->getMockBuilder(VerifyPemCertificate::class)
            ->disableOriginalConstructor()
            ->getMock();
        /** @var  $verifyPemCertificateFactory \PHPUnit\Framework\MockObject\MockObject | \VerifyPemCertificateFactory */
        $verifyPemCertificateFactory = $this->getMockBuilder(VerifyPemCertificateFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $verifyPemCertificateFactory->method('get')->willReturn($this->verifyPemCertificate);

        $this->verifyPadesSignature = new VerifyPadesSignature(
            "pathToValidCA",
            $verifyPemCertificateFactory
        );
    }
    // checkNecessaryFields

    /**
     * @dataProvider missingNecessaryFieldsProvider
     */

    public function testMissingNecessaryFields($signature, $exceptionMessage){

        $this->expectException(Exception::class);
        $this->expectExceptionMessage($exceptionMessage);
        $this->verifyPadesSignature->validateSignature($signature);
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
        $this->verifyPemCertificate
            ->method("parsePemCertificate")
            ->willReturn([
                "validFrom_time_t"=>1502268599,
                "validTo_time_t"=>1502268601
            ]);

        $this->expectNotToPerformAssertions();
        $this->verifyPadesSignature->validateSignature($this->getSignature());
    }

    /**
     * @throws Exception
     */
    public function testCertificateWasInvalidOnSignature(){
        $this->verifyPemCertificate
            ->method("parsePemCertificate")
            ->willReturn([
                "validFrom_time_t"=>0,
                "validTo_time_t"=>0
            ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("La date de la signature 1502268600000 n'entre pas dans la date de validité du certitficat 0 - 0");
        $this->verifyPadesSignature->validateSignatureWithoutCertificateChecking($this->getSignature());
    }

    // Check checkCertificateWithoutCheckingCertificateChain est
    //  - appelé par validateSignature
    //  - pas appelé par validateSignatureWithoutCertificateChecking

    public function testcheckCertificateWithoutCheckingCertificateChainIsCalled(){
        $this->verifyPemCertificate
            ->method("parsePemCertificate")
            ->willReturn([
                "validFrom_time_t"=>1502268599,
                "validTo_time_t"=>1502268601
            ]);

        $this->verifyPemCertificate
            ->expects($this->once())
            ->method("checkCertificateWithoutCheckingCertificateChain");

        $this->verifyPadesSignature->validateSignature($this->getSignature());
    }

    public function testcheckCertificateWithoutCheckingCertificateChainIsNotCalled(){
        $this->verifyPemCertificate
            ->method("parsePemCertificate")
            ->willReturn([
                "validFrom_time_t"=>1502268599,
                "validTo_time_t"=>1502268601
            ]);

        $this->verifyPemCertificate
            ->expects($this->never())
            ->method("checkCertificateWithoutCheckingCertificateChain");

        $this->verifyPadesSignature->validateSignatureWithoutCertificateChecking($this->getSignature());
    }

    // Test que l'exception lancée par checkCertificateWithoutCheckingCertificateChain passe le cas échéant

    public function testcheckCertificateWithoutCheckingCertificateChainExceptionGoesThrough(){
        $this->verifyPemCertificate
            ->method("parsePemCertificate")
            ->willReturn([
                "validFrom_time_t"=>1502268599,
                "validTo_time_t"=>1502268601
            ]);

        $this->verifyPemCertificate
            ->method("checkCertificateWithoutCheckingCertificateChain")
            ->willThrowException(new Exception("Exception de test LahgnjCM"));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Exception de test LahgnjCM");
        $this->verifyPadesSignature->validateSignature($this->getSignature());
    }
}