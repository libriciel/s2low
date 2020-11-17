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

    public function testVerifyARevokedCertificate()
    {
        $baseCertificatesDir =__DIR__."/fixtures/certificats";
        $verificator = new VerifyPKCS7Signature("$baseCertificatesDir/dateOk/revokedFromAC/");

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/certificate revoked/");
        $verificator->checkCertificate("$baseCertificatesDir/dateOk/fullchain.pem");
    }

    public function testVerifyACertificateWithNoRecognizedCA()   #NOUVEAU : si la date est ok, le résultat devrait être ok
    {
        $baseCertificatesDir =__DIR__."/fixtures/certificats";
        $verificator = new VerifyPKCS7Signature("$baseCertificatesDir/dateOk/emptyac/");

        $this->assertTrue($verificator->checkCertificate("$baseCertificatesDir/dateOk/fullchain.pem"));
    }

    public function testVerifyAnExpiredCertificateWithNoRecognizedCA()
    {
        $baseCertificatesDir =__DIR__."/fixtures/certificats";
        $verificator = new VerifyPKCS7Signature("$baseCertificatesDir/dateOk/emptyac/");

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/certificate has expired/");
        $verificator->checkCertificate("$baseCertificatesDir/dateKo/fullchain.pem");
    }

    public function testVerifyAnAutosignedCertificate()            #NOUVEAU : si la date est ok, le résultat devrait être ok
    {
        $baseCertificatesDir =__DIR__."/fixtures/certificats";
        $verificator = new VerifyPKCS7Signature("$baseCertificatesDir/dateOk/emptyac/");

        $this->assertTrue($verificator->checkCertificate("$baseCertificatesDir/autosignedDateOk/cert.pem"));
    }

    public function testVerifyAnExpiredAutosignedCertificate()
    {
        $baseCertificatesDir =__DIR__."/fixtures/certificats";
        $verificator = new VerifyPKCS7Signature("$baseCertificatesDir/dateOk/emptyac/");

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/certificate has expired/");
        $verificator->checkCertificate("$baseCertificatesDir/autosignedDateKo/cert.pem");
    }

}