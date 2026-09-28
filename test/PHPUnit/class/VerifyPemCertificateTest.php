<?php

use S2lowLegacy\Class\VerifyPemCertificate;
use S2lowLegacy\Class\VerifyPemCertificateFactory;

class VerifyPemCertificateTest extends S2lowTestCase
{
    private const BASE_CERTIFICATES_DIR = __DIR__ . "/fixtures/certificats";

    /** @var VerifyPemCertificateFactory  */
    private $verifyPemCertificateFactory;

    public function setUp(): void
    {
        parent::setUp();
        $this->verifyPemCertificateFactory = new VerifyPemCertificateFactory();
    }

    /**
     * @throws \Exception
     */
    public function testVerifyAnOKCertificate()
    {
        $verificator = $this->verifyPemCertificateFactory->get(self::BASE_CERTIFICATES_DIR . "/dateOk/ac/");

        $this->assertTrue($verificator->checkCertificateWithOpenSSL(
            self::BASE_CERTIFICATES_DIR . "/dateOk/fullchain.pem",
            [],
            (new DateTime('01-01-2025'))->getTimestamp()
        ));
    }

    public function testVerifyAnExpiredCertificate()
    {
        $verificator = $this->verifyPemCertificateFactory->get(self::BASE_CERTIFICATES_DIR . "/dateKo/ac/");

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/certificate has expired/");
        $verificator->checkCertificateWithOpenSSL(self::BASE_CERTIFICATES_DIR . "/dateKo/fullchain.pem");
    }

    # Le point limitant de la date de validité de chaine de certification est le myCA.pem, avec
    # Not After : Jun 11 14:00:56 2025 GMT
    # En juin, heure d'été => GMT+02:00

    public function testVerifyJustBeforeItsCaExpires()
    {
        $verificator = $this->verifyPemCertificateFactory->get(self::BASE_CERTIFICATES_DIR . "/dateOk/ac/");

        $this->assertTrue(
            $verificator->checkCertificateWithOpenSSL(
                self::BASE_CERTIFICATES_DIR . "/dateOk/fullchain.pem",
                VerifyPemCertificate::CERTIFICATE_CHAIN_ERRORS,
                mktime(16, 00, 55, 06, 11, 2025)
            )
        );
    }

    public function testVerifyJustAfterItsCaExpires()
    {
        $verificator = $this->verifyPemCertificateFactory->get(self::BASE_CERTIFICATES_DIR . "/dateOk/ac/");

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/certificate has expired/");

        $verificator->checkCertificateWithOpenSSL(
            self::BASE_CERTIFICATES_DIR . "/dateOk/fullchain.pem",
            VerifyPemCertificate::CERTIFICATE_CHAIN_ERRORS,
            mktime(16, 00, 57, 06, 11, 2025)
        );
    }

    public function testVerifyARevokedCertificate()
    {
        $baseCertificatesDir = __DIR__ . "/fixtures/certificats";
        $verificator = $this->verifyPemCertificateFactory->get("$baseCertificatesDir/dateOk/revokedFromAC/");

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/Certificat révoqué/");
        $verificator->checkCertificateWithOpenSSL("$baseCertificatesDir/dateOk/fullchain.pem");
    }

    # Le certificat est révoqué dans la CRL de revokedFromAC avec
    # Revocation Date: Jan 26 15:36:08 2021 GMT

    public function testVerifyARevokedCertificateSignedBeforeRevocation()
    {
        $verificator = $this->verifyPemCertificateFactory->get(self::BASE_CERTIFICATES_DIR . "/dateOk/revokedFromAC/");

        $this->assertTrue(
            $verificator->checkCertificateWithOpenSSL(
                self::BASE_CERTIFICATES_DIR . "/dateOk/fullchain.pem",
                [],
                (new DateTime('Jan 26 15:36:07 2021', new DateTimeZone('GMT')))->getTimestamp()
            )
        );
    }

    public function testVerifyARevokedCertificateSignedAfterRevocation()
    {
        $verificator = $this->verifyPemCertificateFactory->get(self::BASE_CERTIFICATES_DIR . "/dateOk/revokedFromAC/");

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/Certificat révoqué/");
        $verificator->checkCertificateWithOpenSSL(
            self::BASE_CERTIFICATES_DIR . "/dateOk/fullchain.pem",
            [],
            (new DateTime('Jan 26 15:36:09 2021', new DateTimeZone('GMT')))->getTimestamp()
        );
    }

    public function getEmptyTimestamps(): array
    {
        return [
            ['0'],
            [''],
        ];
    }

    /**
     * Comme pour openssl verify, un timestamp vide vaut la date courante (et non le 01/01/1970)
     * @dataProvider getEmptyTimestamps
     */
    public function testVerifyARevokedCertificateWithEmptyTimestamp(string $timestamp)
    {
        $verificator = $this->verifyPemCertificateFactory->get(self::BASE_CERTIFICATES_DIR . "/dateOk/revokedFromAC/");

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/Certificat révoqué/");
        $verificator->checkCertificateWithOpenSSL(
            self::BASE_CERTIFICATES_DIR . "/dateOk/fullchain.pem",
            [],
            $timestamp
        );
    }

    public function testVerifyWrongCertificate()
    {
        $baseCertificatesDir = __DIR__ . "/fixtures/certificats";
        $verificator = $this->verifyPemCertificateFactory->get("$baseCertificatesDir/dateOk/ac/");

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches(
            "/Certificat non valide : impossible d'extraire le issuer hash/"
        );
        $verificator->checkCertificateWithOpenSSL(__DIR__ . "/fixtures/toto.txt");
    }

    public function testVerifyAnExpiredCertificateWithNoRecognizedCA()
    {
        $verificator = $verificator = $this->verifyPemCertificateFactory->get(
            self::BASE_CERTIFICATES_DIR . "/dateOk/emptyac/"
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/unable to get local issuer certificate/");
        $verificator->checkCertificateWithOpenSSL(self::BASE_CERTIFICATES_DIR . "/dateKo/fullchain.pem");
    }

    public function testVerifyAnAutosignedCertificate()
    {
        $verificator = $this->verifyPemCertificateFactory->get(
            self::BASE_CERTIFICATES_DIR . "/dateOk/emptyac/"
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/self-signed certificate/");
        $this->assertTrue($verificator->checkCertificateWithOpenSSL(self::BASE_CERTIFICATES_DIR . "/autosignedDateOk/cert.pem"));
    }

    public function testVerifyAnExpiredAutosignedCertificate()
    {
        $verificator = $this->verifyPemCertificateFactory->get(self::BASE_CERTIFICATES_DIR . "/dateOk/emptyac/");

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/self-signed certificate/");
        $verificator->checkCertificateWithOpenSSL(self::BASE_CERTIFICATES_DIR . "/autosignedDateKo/cert.pem");
    }

    #--------WithoutCheckingCertificateChain----------------------------------------------------------------------------

    public function testVerifyWithoutCheckingCertificateChainAnOKCertificate()
    {
        $verificator = $this->verifyPemCertificateFactory->get(self::BASE_CERTIFICATES_DIR . "/dateOk/ac/");

        $this->assertTrue(
            $verificator->checkCertificateWithOpenSSL(
                self::BASE_CERTIFICATES_DIR . "/dateOk/fullchain.pem",
                VerifyPemCertificate::CERTIFICATE_CHAIN_ERRORS,
                (new DateTime('01-01-2025'))->getTimestamp()
            )
        );
    }

    public function testVerifyWithoutCheckingCertificateChainAnExpiredCertificate()
    {
        $verificator = $this->verifyPemCertificateFactory->get(self::BASE_CERTIFICATES_DIR . "/dateKo/ac/");

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/certificate has expired/");
        $verificator->checkCertificateWithOpenSSL(
            self::BASE_CERTIFICATES_DIR . "/dateKo/fullchain.pem",
            VerifyPemCertificate::CERTIFICATE_CHAIN_ERRORS
        );
    }

    public function testVerifyWithoutCheckingCertificateChainARevokedCertificate()
    {
        $verificator = $this->verifyPemCertificateFactory->get(self::BASE_CERTIFICATES_DIR . "/dateOk/revokedFromAC/");

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/Certificat révoqué/");
        $verificator->checkCertificateWithOpenSSL(
            self::BASE_CERTIFICATES_DIR . "/dateOk/fullchain.pem",
            VerifyPemCertificate::CERTIFICATE_CHAIN_ERRORS
        );
    }

    public function testVerifyWithoutCheckingCertificateChainACertificateWithNoRecognizedCA()   #NOUVEAU : si la date est ok, le résultat devrait être ok
    {
        $verificator = $this->verifyPemCertificateFactory->get(self::BASE_CERTIFICATES_DIR . "/dateOk/emptyac/");

        $this->assertTrue(
            $verificator->checkCertificateWithOpenSSL(
                self::BASE_CERTIFICATES_DIR . "/dateOk/fullchain.pem",
                VerifyPemCertificate::CERTIFICATE_CHAIN_ERRORS
            )
        );
    }

    public function testVerifyWithoutCheckingCertificateChainAnExpiredCertificateWithNoRecognizedCA()
    {
        $verificator = $this->verifyPemCertificateFactory->get(self::BASE_CERTIFICATES_DIR . "/dateOk/emptyac/");

        $this->expectNotToPerformAssertions();
        $verificator->checkCertificateWithOpenSSL(                              //Même si ce n'est pas ok, l'erreur
            self::BASE_CERTIFICATES_DIR . "/dateKo/fullchain.pem",   // n'apparait pas car Openssl verify
            VerifyPemCertificate::CERTIFICATE_CHAIN_ERRORS          // s'arrête avant la vérification
        );
    }

    public function testVerifyWithoutCheckingCertificateChainAnAutosignedCertificate()            #NOUVEAU : si la date est ok, le résultat devrait être ok
    {
        $verificator = $this->verifyPemCertificateFactory->get(self::BASE_CERTIFICATES_DIR . "/dateOk/emptyac/");

        $this->assertTrue(
            $verificator->checkCertificateWithOpenSSL(
                self::BASE_CERTIFICATES_DIR . "/autosignedDateOk/cert.pem",
                VerifyPemCertificate::CERTIFICATE_CHAIN_ERRORS
            )
        );
    }

    public function testVerifyWithoutCheckingCertificateChainAnExpiredAutosignedCertificate()
    {
        $verificator = $this->verifyPemCertificateFactory->get(self::BASE_CERTIFICATES_DIR . "/dateOk/emptyac/");

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(" certificate has expired");
        $verificator->checkCertificateWithOpenSSL(
            self::BASE_CERTIFICATES_DIR . "/autosignedDateKo/cert.pem",
            VerifyPemCertificate::CERTIFICATE_CHAIN_ERRORS
        );
    }
}
