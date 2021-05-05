<?php

class verifyPKCS7SignatureTest extends S2lowTestCase
{
    const BASE_CERTIFICATES_DIR = __DIR__ . "/fixtures/certificats";

    public function testVerifyAnOKCertificate()
    {
        $verificator = new VerifyPKCS7Signature(self::BASE_CERTIFICATES_DIR."/dateOk/ac/");

        $this->assertTrue($verificator->checkCertificate(self::BASE_CERTIFICATES_DIR."/dateOk/fullchain.pem"));
    }

    public function testVerifyAnExpiredCertificate()
    {
        $verificator = new VerifyPKCS7Signature(self::BASE_CERTIFICATES_DIR."/dateKo/ac/");

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/certificate has expired/");
        $verificator->checkCertificate(self::BASE_CERTIFICATES_DIR."/dateKo/fullchain.pem");
    }

# Le point limitant de la date de validité de chaine de certification est le myCA.pem, avec
# Not After : Jun 11 14:00:56 2025 GMT
# En juin, heure d'été => GMT+02:00

    public function testVerifyJustBeforeItsCaExpires()
    {
        $verificator = new VerifyPKCS7Signature(self::BASE_CERTIFICATES_DIR."/dateOk/ac/");

        $this->assertTrue(
            $verificator->checkCertificateWithoutCheckingCertificateChain(
                self::BASE_CERTIFICATES_DIR."/dateOk/fullchain.pem",
                mktime(16,00,55,06,11,2025)
            )
        );
    }

    public function testVerifyJustAfterItsCaExpires()
    {
        $verificator = new VerifyPKCS7Signature(self::BASE_CERTIFICATES_DIR."/dateOk/ac/");

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/certificate has expired/");

        $verificator->checkCertificateWithoutCheckingCertificateChain(
            self::BASE_CERTIFICATES_DIR."/dateOk/fullchain.pem",
                mktime(16,00,57,06,11,2025)
        );
    }

    public function testVerifyARevokedCertificate()
    {
        $verificator = new VerifyPKCS7Signature(self::BASE_CERTIFICATES_DIR."/dateOk/revokedFromAC/");

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/certificate revoked/");
        $verificator->checkCertificate(self::BASE_CERTIFICATES_DIR."/dateOk/fullchain.pem");
    }

    public function testVerifyACertificateWithNoRecognizedCA()
    {
        $verificator = new VerifyPKCS7Signature(self::BASE_CERTIFICATES_DIR."/dateOk/emptyac/");

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/unable to get local issuer certificate/");      #TODO : adapter
        $verificator->checkCertificate(self::BASE_CERTIFICATES_DIR."/dateOk/fullchain.pem");
    }

    public function testVerifyAnExpiredCertificateWithNoRecognizedCA()
    {
        $verificator = new VerifyPKCS7Signature(self::BASE_CERTIFICATES_DIR."/dateOk/emptyac/");

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/unable to get local issuer certificate/");
        $verificator->checkCertificate(self::BASE_CERTIFICATES_DIR."/dateKo/fullchain.pem");
    }

    public function testVerifyAnAutosignedCertificate()
    {
        $verificator = new VerifyPKCS7Signature(self::BASE_CERTIFICATES_DIR."/dateOk/emptyac/");

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/self signed certificate/");      #TODO : adapter
        $this->assertTrue($verificator->checkCertificate(self::BASE_CERTIFICATES_DIR."/autosignedDateOk/cert.pem"));
    }

    public function testVerifyAnExpiredAutosignedCertificate()
    {
        $verificator = new VerifyPKCS7Signature(self::BASE_CERTIFICATES_DIR."/dateOk/emptyac/");

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/self signed certificate/");
        $verificator->checkCertificate(self::BASE_CERTIFICATES_DIR."/autosignedDateKo/cert.pem");
    }

    #--------WithoutCheckingCertificateChain----------------------------------------------------------------------------

    public function testVerifyWithoutCheckingCertificateChainAnOKCertificate()
    {
        $verificator = new VerifyPKCS7Signature(self::BASE_CERTIFICATES_DIR."/dateOk/ac/");

        $this->assertTrue(
            $verificator->checkCertificateWithoutCheckingCertificateChain(
                self::BASE_CERTIFICATES_DIR."/dateOk/fullchain.pem"
            )
        );
    }

    public function testVerifyWithoutCheckingCertificateChainAnExpiredCertificate()
    {
        $verificator = new VerifyPKCS7Signature(self::BASE_CERTIFICATES_DIR."/dateKo/ac/");

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/certificate has expired/");
        $verificator->checkCertificateWithoutCheckingCertificateChain(
            self::BASE_CERTIFICATES_DIR."/dateKo/fullchain.pem"
        );
    }

    public function testVerifyWithoutCheckingCertificateChainARevokedCertificate()
    {
        $verificator = new VerifyPKCS7Signature(self::BASE_CERTIFICATES_DIR."/dateOk/revokedFromAC/");

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/certificate revoked/");
        $verificator->checkCertificateWithoutCheckingCertificateChain(
            self::BASE_CERTIFICATES_DIR."/dateOk/fullchain.pem"
        );
    }

    public function testVerifyWithoutCheckingCertificateChainACertificateWithNoRecognizedCA()   #NOUVEAU : si la date est ok, le résultat devrait être ok
    {
        $verificator = new VerifyPKCS7Signature(
            self::BASE_CERTIFICATES_DIR."/dateOk/emptyac/"
        );

        $this->assertTrue(
            $verificator->checkCertificateWithoutCheckingCertificateChain(
                self::BASE_CERTIFICATES_DIR."/dateOk/fullchain.pem"
            )
        );
    }

    public function testVerifyWithoutCheckingCertificateChainAnExpiredCertificateWithNoRecognizedCA()
    {
        $verificator = new VerifyPKCS7Signature(self::BASE_CERTIFICATES_DIR."/dateOk/emptyac/");

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/La date de la signature .*? n'entre pas dans la date de validité du certificat .*? - .*?/");
        $verificator->checkCertificateWithoutCheckingCertificateChain(
            self::BASE_CERTIFICATES_DIR."/dateKo/fullchain.pem"
        );
    }

    public function testVerifyWithoutCheckingCertificateChainAnAutosignedCertificate()            #NOUVEAU : si la date est ok, le résultat devrait être ok
    {
        $verificator = new VerifyPKCS7Signature(
            self::BASE_CERTIFICATES_DIR."/dateOk/emptyac/"
        );

        $this->assertTrue(
            $verificator->checkCertificateWithoutCheckingCertificateChain(
                self::BASE_CERTIFICATES_DIR."/autosignedDateOk/cert.pem"
            )
        );
    }

    public function testVerifyWithoutCheckingCertificateChainAnExpiredAutosignedCertificate()
    {
        $verificator = new VerifyPKCS7Signature(
            self::BASE_CERTIFICATES_DIR."/dateOk/emptyac/"
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/La date de la signature .*? n'entre pas dans la date de validité du certificat .*? - .*?/");
        $verificator->checkCertificateWithoutCheckingCertificateChain(
            self::BASE_CERTIFICATES_DIR."/autosignedDateKo/cert.pem"
        );
    }
}