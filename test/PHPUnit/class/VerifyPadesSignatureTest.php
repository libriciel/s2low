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

    private function extractSignatureFromPadesValidJson($string){
        return json_decode($string)->signatures[0];
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

    /**
     * @throws Exception
     */
    public function testValidateSigned(){
        $rgs_validca_path = __DIR__ . "/../lib/fixtures/validca/";
        $verifyPadesSignature = new VerifyPadesSignature(
            $rgs_validca_path,
            new VerifyPemCertificateFactory()
        );

        $signature = $this->extractSignatureFromPadesValidJson(
            file_get_contents(__DIR__."/fixtures/signature-pades/return-courrier-signe.json")
        );

        $exceptionThrown = false;
        try{
            $verifyPadesSignature->validateSignature($signature);
        } catch (Exception $exception){
            $exceptionThrown = true;
        }
        $this->assertFalse($exceptionThrown);
    }

    /**
     * @throws Exception
     */
    public function testNotValidateSigned(){
        $rgs_validca_path = __DIR__ . "/../lib/fixtures/validca/";
        $verifyPadesSignature = new VerifyPadesSignature(
            $rgs_validca_path,
            new VerifyPemCertificateFactory()
        );

        $signature = $this->extractSignatureFromPadesValidJson(
            file_get_contents(__DIR__."/fixtures/signature-pades/return-courrier-alter.json")
        );

        $exceptionThrown = false;
        try{
            $verifyPadesSignature->validateSignature($signature);
        } catch (Exception $exception){
            $exceptionThrown = true;
            $this->assertEquals(
                "Au moins une signature n'est pas valide",
                $exception->getMessage()
            );
        }

        $this->assertTrue($exceptionThrown);
    }

    /**
     * @throws Exception
     */
    public function testValidateSignedNoCertificatCheking(){
        $rgs_validca_path = __DIR__ . "/../lib/fixtures/validca/";
        $verifyPadesSignature = new VerifyPadesSignature(
            $rgs_validca_path,
            new VerifyPemCertificateFactory()
        );

        $signature = $this->extractSignatureFromPadesValidJson(
            file_get_contents(__DIR__."/fixtures/signature-pades/return-courrier-signe.json")
        );
        $exceptionThrown = false;
        try{
        $verifyPadesSignature->validateSignatureWithoutCertificateChecking($signature);
        } catch (Exception $exception){
            $exceptionThrown = true;
        }
        $this->assertFalse($exceptionThrown);
    }

    /**
     * @throws Exception
     */
    public function testValidateSignedBadCertificate(){
        $signature = $this->extractSignatureFromPadesValidJson(
            file_get_contents(__DIR__."/fixtures/signature-pades/return-courrier-signe.json")
        );

        $this->verifyPemCertificate
            ->method("parsePemCertificate")
            ->willReturn([
                "validFrom_time_t"=>1502268599,
                "validTo_time_t"=>1502268601
            ]);

        $this->verifyPemCertificate
            ->method('checkCertificateWithoutCheckingCertificateChain')
            ->willThrowException(new Exception("problème"));

        $exceptionThrown = false;
        try{
            $this->verifyPadesSignature->validateSignature($signature);
        } catch (Exception $exception){
            $exceptionThrown = true;
            $this->assertEquals(
                "problème",
                $exception->getMessage()
            );
        }
        $this->assertTrue($exceptionThrown);
    }

    /**
     * @throws Exception
     */
    public function testValidateSignedOutdatedCertificate(){
        $signature = $this->extractSignatureFromPadesValidJson(
            file_get_contents(__DIR__."/fixtures/signature-pades/return-courrier-signe.json")
        );

        $this->verifyPemCertificate
            ->method("parsePemCertificate")
            ->willReturn([
                "validFrom_time_t"=>0,
                "validTo_time_t"=>0
            ]);

        $exceptionThrown = false;
        try{
            $this->verifyPadesSignature->validateSignature($signature);
        } catch (Exception $exception){
            $exceptionThrown = true;
            $this->assertEquals(
                "La date de la signature 1502268600000 n'entre pas dans la date de validité du certitficat 0 - 0",
                $exception->getMessage()
            );
        }
        $this->assertTrue($exceptionThrown);
    }

    public function testMissingNecessaryFields(){
        $signature = $this->getMockBuilder(stdClass::class)
            ->disableOriginalConstructor()
            ->getMock();

        $signature->valid = false;

        $exceptionThrown = false;
        try{
            $this->verifyPadesSignature->validateSignature($signature);
        } catch (Exception $exception){
            $exceptionThrown = true;
            $this->assertEquals(
                "Au moins une signature n'est pas valide",
                $exception->getMessage()
            );
        }
        $this->assertTrue($exceptionThrown);
    }

    public function testMissingNecessaryFields2(){
        $signature = $this->getMockBuilder(stdClass::class)
            ->disableOriginalConstructor()
            ->getMock();

        $signature->valid = true;
        $signature->signingCert = "";

        $exceptionThrown = false;
        try{
            $this->verifyPadesSignature->validateSignature($signature);
        } catch (Exception $exception){
            $exceptionThrown = true;
            $this->assertEquals(
                "Impossible de récupérer le certificat de signature",
                $exception->getMessage()
            );
        }
        $this->assertTrue($exceptionThrown);
    }

    public function testMissingNecessaryFields3(){
        $signature = $this->getMockBuilder(stdClass::class)
            ->disableOriginalConstructor()
            ->getMock();

        $signature->valid = true;
        $signature->signingCert = "certificat";
        $signature->signatureDate ="";

        $exceptionThrown = false;
        try{
            $this->verifyPadesSignature->validateSignature($signature);
        } catch (Exception $exception){
            $exceptionThrown = true;
            $this->assertEquals(
                "Impossible de determiner la date de la signature",
                $exception->getMessage()
            );
        }
        $this->assertTrue($exceptionThrown);
    }
}