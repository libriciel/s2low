<?php

use S2low\Services\ProcessCommand\CommandLauncher;
use S2low\Services\ProcessCommand\OpenSSLWrapper;
use S2lowLegacy\Class\VerifyPemCertificate;

class VerifyPemCertificateTest extends S2lowTestCase
{
    private const BASE_CERTIFICATES_DIR = __DIR__ . "/fixtures/certificats";


    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @throws \Exception
     */
    public function testVerifyAnOKCertificate()
    {
        $verificator = $this->getVerifyPemCertificate();

        $this->assertTrue($verificator->checkCertificateWithOpenSSL(
            self::BASE_CERTIFICATES_DIR . "/dateOk/fullchain.pem",
            self::BASE_CERTIFICATES_DIR . "/dateOk/ac/",
            [],
            (new DateTime('01-01-2025'))->getTimestamp()
        ));
    }

    public function testVerifyAnExpiredCertificate()
    {
        $verificator = $this->getVerifyPemCertificate();

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/certificate has expired/");
        $verificator->checkCertificateWithOpenSSL(
            self::BASE_CERTIFICATES_DIR . "/dateKo/fullchain.pem",
            self::BASE_CERTIFICATES_DIR . "/dateKo/ac/"
        );
    }

    # Le point limitant de la date de validité de chaine de certification est le myCA.pem, avec
    # Not After : Jun 11 14:00:56 2025 GMT
    # En juin, heure d'été => GMT+02:00

    public function testVerifyJustBeforeItsCaExpires()
    {
        $verificator = $this->getVerifyPemCertificate();

        $this->assertTrue(
            $verificator->checkCertificateWithOpenSSL(
                self::BASE_CERTIFICATES_DIR . "/dateOk/fullchain.pem",
                self::BASE_CERTIFICATES_DIR . "/dateOk/ac/",
                VerifyPemCertificate::CERTIFICATE_CHAIN_ERRORS,
                mktime(16, 00, 55, 06, 11, 2025)
            )
        );
    }

    public function testVerifyJustAfterItsCaExpires()
    {
        $verificator = $this->getVerifyPemCertificate();

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/certificate has expired/");

        $verificator->checkCertificateWithOpenSSL(
            self::BASE_CERTIFICATES_DIR . "/dateOk/fullchain.pem",
            self::BASE_CERTIFICATES_DIR . "/dateOk/ac/",
            VerifyPemCertificate::CERTIFICATE_CHAIN_ERRORS,
            mktime(16, 00, 57, 06, 11, 2025)
        );
    }

    public function testVerifyARevokedCertificate()
    {
        $baseCertificatesDir = __DIR__ . "/fixtures/certificats";
        $verificator = $this->getVerifyPemCertificate();

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/Certificat révoqué/");
        $verificator->checkCertificateWithOpenSSL(
            "$baseCertificatesDir/dateOk/fullchain.pem",
            "$baseCertificatesDir/dateOk/revokedFromAC/"
        );
    }

    public function testVerifyWrongCertificate()
    {
        $baseCertificatesDir = __DIR__ . "/fixtures/certificats";
        $verificator = $this->getVerifyPemCertificate();

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches(
            "/Certificat non valide : impossible d'extraire le issuer hash/"
        );
        $verificator->checkCertificateWithOpenSSL(
            __DIR__ . "/fixtures/toto.txt",
            "$baseCertificatesDir/dateOk/ac/"
        );
    }

    public function testVerifyAnExpiredCertificateWithNoRecognizedCA()
    {
        $verificator = $verificator = $this->getVerifyPemCertificate();

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/unable to get local issuer certificate/");
        $verificator->checkCertificateWithOpenSSL(
            self::BASE_CERTIFICATES_DIR . "/dateKo/fullchain.pem",
            self::BASE_CERTIFICATES_DIR . "/dateOk/emptyac/"
        );
    }

    public function testVerifyAnAutosignedCertificate()
    {
        $verificator = $this->getVerifyPemCertificate();

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/self-signed certificate/");
        $this->assertTrue($verificator->checkCertificateWithOpenSSL(
            self::BASE_CERTIFICATES_DIR . "/autosignedDateOk/cert.pem",
            self::BASE_CERTIFICATES_DIR . "/dateOk/emptyac/"
        ));
    }

    public function testVerifyAnExpiredAutosignedCertificate()
    {
        $verificator = $this->getVerifyPemCertificate();

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/self-signed certificate/");
        $verificator->checkCertificateWithOpenSSL(
            self::BASE_CERTIFICATES_DIR . "/autosignedDateKo/cert.pem",
            self::BASE_CERTIFICATES_DIR . "/dateOk/emptyac/"
        );
    }

    #--------WithoutCheckingCertificateChain----------------------------------------------------------------------------

    public function testVerifyWithoutCheckingCertificateChainAnOKCertificate()
    {
        $verificator = $this->getVerifyPemCertificate();

        $this->assertTrue(
            $verificator->checkCertificateWithOpenSSL(
                self::BASE_CERTIFICATES_DIR . "/dateOk/fullchain.pem",
                self::BASE_CERTIFICATES_DIR . "/dateOk/ac/",
                VerifyPemCertificate::CERTIFICATE_CHAIN_ERRORS,
                (new DateTime('01-01-2025'))->getTimestamp()
            )
        );
    }

    public function testVerifyWithoutCheckingCertificateChainAnExpiredCertificate()
    {
        $verificator = $this->getVerifyPemCertificate();

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/certificate has expired/");
        $verificator->checkCertificateWithOpenSSL(
            self::BASE_CERTIFICATES_DIR . "/dateKo/fullchain.pem",
            self::BASE_CERTIFICATES_DIR . "/dateKo/ac/",
            VerifyPemCertificate::CERTIFICATE_CHAIN_ERRORS
        );
    }

    public function testVerifyWithoutCheckingCertificateChainARevokedCertificate()
    {
        $verificator = $this->getVerifyPemCertificate();

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/Certificat révoqué/");
        $verificator->checkCertificateWithOpenSSL(
            self::BASE_CERTIFICATES_DIR . "/dateOk/fullchain.pem",
            self::BASE_CERTIFICATES_DIR . "/dateOk/revokedFromAC/",
            VerifyPemCertificate::CERTIFICATE_CHAIN_ERRORS
        );
    }

    public function testVerifyWithoutCheckingCertificateChainACertificateWithNoRecognizedCA()   #NOUVEAU : si la date est ok, le résultat devrait être ok
    {
        $verificator = $this->getVerifyPemCertificate();

        $this->assertTrue(
            $verificator->checkCertificateWithOpenSSL(
                self::BASE_CERTIFICATES_DIR . "/dateOk/fullchain.pem",
                self::BASE_CERTIFICATES_DIR . "/dateOk/emptyac/",
                VerifyPemCertificate::CERTIFICATE_CHAIN_ERRORS
            )
        );
    }

    public function testVerifyWithoutCheckingCertificateChainAnExpiredCertificateWithNoRecognizedCA()
    {
        $verificator = $this->getVerifyPemCertificate();

        $this->expectNotToPerformAssertions();
        $verificator->checkCertificateWithOpenSSL(                              //Même si ce n'est pas ok, l'erreur
            self::BASE_CERTIFICATES_DIR . "/dateKo/fullchain.pem",   // n'apparait pas car Openssl verify
            self::BASE_CERTIFICATES_DIR . "/dateOk/emptyac/",
            VerifyPemCertificate::CERTIFICATE_CHAIN_ERRORS          // s'arrête avant la vérification
        );
    }

    public function testVerifyWithoutCheckingCertificateChainAnAutosignedCertificate()            #NOUVEAU : si la date est ok, le résultat devrait être ok
    {
        $verificator = $this->getVerifyPemCertificate();

        $this->assertTrue(
            $verificator->checkCertificateWithOpenSSL(
                self::BASE_CERTIFICATES_DIR . "/autosignedDateOk/cert.pem",
                self::BASE_CERTIFICATES_DIR . "/dateOk/emptyac/",
                VerifyPemCertificate::CERTIFICATE_CHAIN_ERRORS
            )
        );
    }

    public function testVerifyWithoutCheckingCertificateChainAnExpiredAutosignedCertificate()
    {
        $verificator = $this->getVerifyPemCertificate();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(" certificate has expired");
        $verificator->checkCertificateWithOpenSSL(
            self::BASE_CERTIFICATES_DIR . "/autosignedDateKo/cert.pem",
            self::BASE_CERTIFICATES_DIR . "/dateOk/emptyac/",
            VerifyPemCertificate::CERTIFICATE_CHAIN_ERRORS
        );
    }

    /**
     * @return \S2lowLegacy\Class\VerifyPemCertificate
     */
    private function getVerifyPemCertificate(): VerifyPemCertificate
    {
        return new VerifyPemCertificate(new OpenSSLWrapper(new CommandLauncher()));
    }
}
