<?php

class verifyPKCS7SignatureTest extends S2lowTestCase
{
    public function testVerifyAnOKCertificate()
    {
        $baseCertificatesDir =__DIR__."/fixtures/certificats";
        $verificator = new VerifyPKCS7Signature("$baseCertificatesDir/dateOk/ac/");

        $this->assertTrue($verificator->checkCertificate("$baseCertificatesDir/dateOk/fullchain.pem"));
    }

    public function testVerifyAnExpiredCertificate()
    {
        $baseCertificatesDir =__DIR__."/fixtures/certificats";
        $verificator = new VerifyPKCS7Signature("$baseCertificatesDir/dateKo/ac/");

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/certificate has expired/");
        $verificator->checkCertificate("$baseCertificatesDir/dateKo/fullchain.pem");
    }

    public function testVerifyJustBeforeItsCaExpires()
    {
        $baseCertificatesDir =__DIR__."/fixtures/certificats";
        $verificator = new VerifyPKCS7Signature("$baseCertificatesDir/dateOk/ac/");

        $this->assertTrue(
            $verificator->checkCertificate(
                "$baseCertificatesDir/dateOk/fullchain.pem",
                mktime(14,00,55,06,11,2025)
            )
        );
    }

    public function testVerifyJustAfterItsCaExpires()
    {
        $baseCertificatesDir =__DIR__."/fixtures/certificats";
        $verificator = new VerifyPKCS7Signature("$baseCertificatesDir/dateOk/ac/");

        $this->assertTrue(
            $verificator->checkCertificate(
                "$baseCertificatesDir/dateOk/fullchain.pem",
                mktime(14,00,57,06,11,2025)
            )
        );
    }

    public function testVerifyARevokedCertificate()
    {
        $baseCertificatesDir =__DIR__."/fixtures/certificats";
        $verificator = new VerifyPKCS7Signature("$baseCertificatesDir/dateOk/revokedFromAC/");

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/certificate revoked/");
        $verificator->checkCertificate("$baseCertificatesDir/dateOk/fullchain.pem");
    }

    public function testVerifyACertificateWithNoRecognizedCA()
    {
        $baseCertificatesDir =__DIR__."/fixtures/certificats";
        $verificator = new VerifyPKCS7Signature("$baseCertificatesDir/dateOk/emptyac/");

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/unable to get local issuer certificate/");      #TODO : adapter
        $verificator->checkCertificate("$baseCertificatesDir/dateOk/fullchain.pem");
    }

    public function testVerifyAnExpiredCertificateWithNoRecognizedCA()
    {
        $baseCertificatesDir =__DIR__."/fixtures/certificats";
        $verificator = new VerifyPKCS7Signature("$baseCertificatesDir/dateOk/emptyac/");

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/unable to get local issuer certificate/");
        $verificator->checkCertificate("$baseCertificatesDir/dateKo/fullchain.pem");
    }

    public function testVerifyAnAutosignedCertificate()
    {
        $baseCertificatesDir =__DIR__."/fixtures/certificats";
        $verificator = new VerifyPKCS7Signature("$baseCertificatesDir/dateOk/emptyac/");

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/self signed certificate/");      #TODO : adapter
        $this->assertTrue($verificator->checkCertificate("$baseCertificatesDir/autosignedDateOk/cert.pem"));
    }

    public function testVerifyAnExpiredAutosignedCertificate()
    {
        $baseCertificatesDir =__DIR__."/fixtures/certificats";
        $verificator = new VerifyPKCS7Signature("$baseCertificatesDir/dateOk/emptyac/");

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/self signed certificate/");
        $verificator->checkCertificate("$baseCertificatesDir/autosignedDateKo/cert.pem");
    }

    #--------WithoutCheckingCertificateChain----------------------------------------------------------------------------

    public function testVerifyWithoutCheckingCertificateChainAnOKCertificate()
    {
        $baseCertificatesDir =__DIR__."/fixtures/certificats";
        $verificator = new VerifyPKCS7Signature("$baseCertificatesDir/dateOk/ac/");

        $this->assertTrue($verificator->checkCertificateWithoutCheckingCertificateChain("$baseCertificatesDir/dateOk/fullchain.pem"));
    }

    public function testVerifyWithoutCheckingCertificateChainAnExpiredCertificate()
    {
        $baseCertificatesDir =__DIR__."/fixtures/certificats";
        $verificator = new VerifyPKCS7Signature("$baseCertificatesDir/dateKo/ac/");

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/certificate has expired/");
        $verificator->checkCertificateWithoutCheckingCertificateChain("$baseCertificatesDir/dateKo/fullchain.pem");
    }

    public function testVerifyWithoutCheckingCertificateChainARevokedCertificate()
    {
        $baseCertificatesDir =__DIR__."/fixtures/certificats";
        $verificator = new VerifyPKCS7Signature("$baseCertificatesDir/dateOk/revokedFromAC/");

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/certificate revoked/");
        $verificator->checkCertificateWithoutCheckingCertificateChain("$baseCertificatesDir/dateOk/fullchain.pem");
    }

    public function testVerifyWithoutCheckingCertificateChainACertificateWithNoRecognizedCA()   #NOUVEAU : si la date est ok, le résultat devrait être ok
    {
        $baseCertificatesDir =__DIR__."/fixtures/certificats";
        $verificator = new VerifyPKCS7Signature("$baseCertificatesDir/dateOk/emptyac/");

        $this->assertTrue($verificator->checkCertificateWithoutCheckingCertificateChain("$baseCertificatesDir/dateOk/fullchain.pem"));
    }

    public function testVerifyWithoutCheckingCertificateChainAnExpiredCertificateWithNoRecognizedCA()
    {
        $baseCertificatesDir =__DIR__."/fixtures/certificats";
        $verificator = new VerifyPKCS7Signature("$baseCertificatesDir/dateOk/emptyac/");

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/certificate has expired/");
        $verificator->checkCertificateWithoutCheckingCertificateChain("$baseCertificatesDir/dateKo/fullchain.pem");
    }

    public function testVerifyWithoutCheckingCertificateChainAnAutosignedCertificate()            #NOUVEAU : si la date est ok, le résultat devrait être ok
    {
        $baseCertificatesDir =__DIR__."/fixtures/certificats";
        $verificator = new VerifyPKCS7Signature("$baseCertificatesDir/dateOk/emptyac/");

        $this->assertTrue($verificator->checkCertificateWithoutCheckingCertificateChain("$baseCertificatesDir/autosignedDateOk/cert.pem"));
    }

    public function testVerifyWithoutCheckingCertificateChainAnExpiredAutosignedCertificate()
    {
        $baseCertificatesDir =__DIR__."/fixtures/certificats";
        $verificator = new VerifyPKCS7Signature("$baseCertificatesDir/dateOk/emptyac/");

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/certificate has expired/");
        $verificator->checkCertificateWithoutCheckingCertificateChain("$baseCertificatesDir/autosignedDateKo/cert.pem");
    }
}