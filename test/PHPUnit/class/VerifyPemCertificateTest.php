<?php

use S2lowLegacy\Class\VerifyPemCertificate;

class VerifyPemCertificateTest extends S2lowTestCase
{
    private readonly VerifyPemCertificate $verifyPemCertificate;
    private const BASE_CERTIFICATES_DIR = __DIR__ . "/fixtures/certificats";

    public function setUp(): void
    {
        parent::setUp();
        $this->verifyPemCertificate = $this->getObjectInstancier()->get(VerifyPemCertificate::class);
    }

    /**
     * @throws \Exception
     */
    public function testVerifyAnOKCertificate()
    {
        $caCertificatesPath = self::BASE_CERTIFICATES_DIR . "/dateOk/ac/";

        $this->assertTrue(
            $this->verifyPemCertificate->checkCertificateWithOpenSSL(
                self::BASE_CERTIFICATES_DIR . "/dateOk/fullchain.pem",
                $caCertificatesPath,
                [],
                (new DateTime('01-01-2025'))->getTimestamp()
            )
        );
    }

    public function testVerifyAnExpiredCertificate()
    {
        $caCertificatesPath = self::BASE_CERTIFICATES_DIR . "/dateKo/ac/";

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/certificate has expired/");
        $this->verifyPemCertificate->checkCertificateWithOpenSSL(
            self::BASE_CERTIFICATES_DIR . "/dateKo/fullchain.pem",
            $caCertificatesPath,
        );
    }

    # Le point limitant de la date de validité de chaine de certification est le myCA.pem, avec
    # Not After : Jun 11 14:00:56 2025 GMT
    # En juin, heure d'été => GMT+02:00

    public function testVerifyJustBeforeItsCaExpires()
    {
        $caCertificatesPath = self::BASE_CERTIFICATES_DIR . "/dateOk/ac/";

        $this->assertTrue(
            $this->verifyPemCertificate->checkCertificateWithOpenSSL(
                self::BASE_CERTIFICATES_DIR . "/dateOk/fullchain.pem",
                $caCertificatesPath,
                VerifyPemCertificate::CERTIFICATE_CHAIN_ERRORS,
                mktime(16, 00, 55, 06, 11, 2025)
            )
        );
    }

    public function testVerifyJustAfterItsCaExpires()
    {
        $caCertificatesPath = self::BASE_CERTIFICATES_DIR . "/dateOk/ac/";

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/certificate has expired/");

        $this->verifyPemCertificate->checkCertificateWithOpenSSL(
            self::BASE_CERTIFICATES_DIR . "/dateOk/fullchain.pem",
            $caCertificatesPath,
            VerifyPemCertificate::CERTIFICATE_CHAIN_ERRORS,
            mktime(16, 00, 57, 06, 11, 2025)
        );
    }

    public function testVerifyARevokedCertificate()
    {
        $baseCertificatesDir = __DIR__ . "/fixtures/certificats";
        $caCertificatesPath = "$baseCertificatesDir/dateOk/revokedFromAC/";

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/Certificat révoqué/");
        $this->verifyPemCertificate->checkCertificateWithOpenSSL(
            "$baseCertificatesDir/dateOk/fullchain.pem",
            $caCertificatesPath
        );
    }

    public function testVerifyWrongCertificate()
    {
        $baseCertificatesDir = __DIR__ . "/fixtures/certificats";
        $caCertificatesPath = "$baseCertificatesDir/dateOk/ac/";

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches(
            "/Certificat non valide : impossible d'extraire le issuer hash/"
        );
        $this->verifyPemCertificate->checkCertificateWithOpenSSL(
            __DIR__ . "/fixtures/toto.txt",
            $caCertificatesPath,
        );
    }

    public function testVerifyAnExpiredCertificateWithNoRecognizedCA()
    {
        $caCertificatesPath = self::BASE_CERTIFICATES_DIR . "/dateOk/emptyac/";

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/unable to get local issuer certificate/");
        $this->verifyPemCertificate->checkCertificateWithOpenSSL(
            self::BASE_CERTIFICATES_DIR . "/dateKo/fullchain.pem",
            $caCertificatesPath
        );
    }

    public function testVerifyAnAutosignedCertificate()
    {
        $caCertificatesPath = self::BASE_CERTIFICATES_DIR . "/dateOk/emptyac/";

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/self-signed certificate/");
        $this->assertTrue($this->verifyPemCertificate->checkCertificateWithOpenSSL(
            self::BASE_CERTIFICATES_DIR . "/autosignedDateOk/cert.pem",
            $caCertificatesPath
        ));
    }

    public function testVerifyAnExpiredAutosignedCertificate()
    {
        $caCertificatesPath = self::BASE_CERTIFICATES_DIR . "/dateOk/emptyac/";

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/self-signed certificate/");
        $this->verifyPemCertificate->checkCertificateWithOpenSSL(
            self::BASE_CERTIFICATES_DIR . "/autosignedDateKo/cert.pem",
            $caCertificatesPath
        );
    }

    #--------WithoutCheckingCertificateChain----------------------------------------------------------------------------

    public function testVerifyWithoutCheckingCertificateChainAnOKCertificate()
    {
        $caCertificatesPath = self::BASE_CERTIFICATES_DIR . "/dateOk/ac/";

        $this->assertTrue(
            $this->verifyPemCertificate->checkCertificateWithOpenSSL(
                self::BASE_CERTIFICATES_DIR . "/dateOk/fullchain.pem",
                $caCertificatesPath,
                VerifyPemCertificate::CERTIFICATE_CHAIN_ERRORS,
                (new DateTime('01-01-2025'))->getTimestamp()
            )
        );
    }

    public function testVerifyWithoutCheckingCertificateChainAnExpiredCertificate()
    {
        $caCertificatesPath = self::BASE_CERTIFICATES_DIR . "/dateKo/ac/";

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/certificate has expired/");
        $this->verifyPemCertificate->checkCertificateWithOpenSSL(
            self::BASE_CERTIFICATES_DIR . "/dateKo/fullchain.pem",
            $caCertificatesPath,
            VerifyPemCertificate::CERTIFICATE_CHAIN_ERRORS
        );
    }

    public function testVerifyWithoutCheckingCertificateChainARevokedCertificate()
    {
        $caCertificatesPath = self::BASE_CERTIFICATES_DIR . "/dateOk/revokedFromAC/";

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/Certificat révoqué/");
        $this->verifyPemCertificate->checkCertificateWithOpenSSL(
            self::BASE_CERTIFICATES_DIR . "/dateOk/fullchain.pem",
            $caCertificatesPath,
            VerifyPemCertificate::CERTIFICATE_CHAIN_ERRORS
        );
    }

    public function testVerifyWithoutCheckingCertificateChainACertificateWithNoRecognizedCA()   #NOUVEAU : si la date est ok, le résultat devrait être ok
    {
        $caCertificatesPath = self::BASE_CERTIFICATES_DIR . "/dateOk/emptyac/";

        $this->assertTrue(
            $this->verifyPemCertificate->checkCertificateWithOpenSSL(
                self::BASE_CERTIFICATES_DIR . "/dateOk/fullchain.pem",
                $caCertificatesPath,
                VerifyPemCertificate::CERTIFICATE_CHAIN_ERRORS
            )
        );
    }

    public function testVerifyWithoutCheckingCertificateChainAnExpiredCertificateWithNoRecognizedCA()
    {
        $caCertificatesPath = self::BASE_CERTIFICATES_DIR . "/dateOk/emptyac/";

        $this->expectNotToPerformAssertions();
        $this->verifyPemCertificate->checkCertificateWithOpenSSL(                              //Même si ce n'est pas ok, l'erreur
            self::BASE_CERTIFICATES_DIR . "/dateKo/fullchain.pem",   // n'apparait pas car Openssl verify
            $caCertificatesPath,
            VerifyPemCertificate::CERTIFICATE_CHAIN_ERRORS          // s'arrête avant la vérification
        );
    }

    public function testVerifyWithoutCheckingCertificateChainAnAutosignedCertificate()            #NOUVEAU : si la date est ok, le résultat devrait être ok
    {
        $caCertificatesPath = self::BASE_CERTIFICATES_DIR . "/dateOk/emptyac/";

        $this->assertTrue(
            $this->verifyPemCertificate->checkCertificateWithOpenSSL(
                self::BASE_CERTIFICATES_DIR . "/autosignedDateOk/cert.pem",
                $caCertificatesPath,
                VerifyPemCertificate::CERTIFICATE_CHAIN_ERRORS
            )
        );
    }

    public function testVerifyWithoutCheckingCertificateChainAnExpiredAutosignedCertificate()
    {
        $caCertificatesPath = self::BASE_CERTIFICATES_DIR . "/dateOk/emptyac/";

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(" certificate has expired");
        $this->verifyPemCertificate->checkCertificateWithOpenSSL(
            self::BASE_CERTIFICATES_DIR . "/autosignedDateKo/cert.pem",
            $caCertificatesPath,
            VerifyPemCertificate::CERTIFICATE_CHAIN_ERRORS
        );
    }
}
