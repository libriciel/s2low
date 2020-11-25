<?php


class VerifyPadesSignatureTest extends S2lowTestCase
{
    /**
     * @throws Exception
     */
    public function testValidateNotSigned(){
        $rgs_validca_path = __DIR__ . "/../lib/fixtures/validca/";
        $verifyPadesSignature = new VerifyPadesSignature(new VerifyPemCertificate($rgs_validca_path));
        //$this->padesValid->setCurlWrapperFactory($this->getCurlWrapperFactory('{"signatures":[],"signed":false}'));
        $verifyPadesSignature->validateSignature('{"signatures":[],"signed":false}');
        $this->assertFalse($this->padesValid->validate(__DIR__."/fixtures/signature-pades/Courrier.pdf"));
    }

    /**
     * @throws Exception
     */
    public function testValidateSigned(){
        $rgs_validca_path = __DIR__ . "/../lib/fixtures/validca/";
        $verifyPadesSignature = new VerifyPadesSignature(new VerifyPemCertificate($rgs_validca_path));

        $retourPades = json_decode(
            file_get_contents(__DIR__."/fixtures/signature-pades/return-courrier-signe.json")
        );
        $signature = $retourPades->signatures[0];

        $this->assertTrue(
            $verifyPadesSignature->validateSignature($signature)
        );
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