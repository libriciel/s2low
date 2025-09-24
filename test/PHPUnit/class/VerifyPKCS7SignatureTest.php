<?php

declare(strict_types=1);

use S2low\Services\Certificates\CRLReader;
use S2low\Services\ProcessCommand\CheckSnInCRLFactory;
use S2low\Services\ProcessCommand\CommandLauncher;
use S2low\Services\ProcessCommand\OpenSSLWrapper;
use S2lowLegacy\Class\VerifyPemCertificate;
use S2lowLegacy\Class\VerifyPemCertificateFactory;
use S2lowLegacy\Class\VerifyPKCS7Signature;
use S2lowLegacy\Lib\PemCertificateFactory;

class VerifyPKCS7SignatureTest extends S2lowTestCase
{
    #-------Test des signatures----------------------------------------------------------------------------------------
    # Signature g�n�r�e selon https://stackoverflow.com/questions/56013953/how-to-verify-a-file-and-a-p7s-detached-signature-with-openssl
    #------------------------------------------------------------------------------------------------------------------

    /**
     * @throws Exception
     */
    public function testRightFileWithSignature()
    {
        $verifyPKCS7Signature = new VerifyPKCS7Signature(
            __DIR__ . '/fixtures/signaturesPKCS7/ac',
            new VerifyPemCertificateFactory(),
            new PemCertificateFactory(),
            new OpenSSLWrapper(
                __DIR__ . '/fixtures/signaturesPKCS7/ac',
                new CommandLauncher(),
                new CheckSnInCRLFactory(new CRLReader())
            )
        );

        static::assertTrue(
            $verifyPKCS7Signature->verifySignature(
                file_get_contents(__DIR__ . '/fixtures/signaturesPKCS7/test_pdf.pdf.p7s'),
                [],
                __DIR__ . '/fixtures/signaturesPKCS7/test_pdf.pdf',
                new DateTime('01-01-2025')
            )
        );
    }

    public function testWrongFileWithSignature()
    {
        $verifyPKCS7Signature = new VerifyPKCS7Signature(
            __DIR__ . '/fixtures/signaturesPKCS7/ac',
            new VerifyPemCertificateFactory(),
            new PemCertificateFactory(),
            new OpenSSLWrapper(
                __DIR__ . '/fixtures/signaturesPKCS7/ac',
                new CommandLauncher(),
                new CheckSnInCRLFactory(new CRLReader())
            )
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La vérification de la signature a échoué');
        $verifyPKCS7Signature->verifySignature(
            file_get_contents(__DIR__ . '/fixtures/signaturesPKCS7/test_pdf.pdf.p7s'),
            [],
            __DIR__ . '/fixtures/toto.txt',
            new DateTime('01-01-2025')
        );
    }

    public function testRightFileWithWrongAC()
    {
        $verifyPKCS7Signature = new VerifyPKCS7Signature(
            __DIR__ . '/',
            new VerifyPemCertificateFactory(),
            new PemCertificateFactory(),
            new OpenSSLWrapper(
                __DIR__ . '/',
                new CommandLauncher(),
                new CheckSnInCRLFactory(new CRLReader())
            )
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(' unable to get local issuer certificate');
        $verifyPKCS7Signature->verifySignature(
            file_get_contents(__DIR__ . '/fixtures/signaturesPKCS7/test_pdf.pdf.p7s'),
            [],
            __DIR__ . '/fixtures/signaturesPKCS7/test_pdf.pdf',
            new DateTime('01-01-2025')
        );
    }

    // Test des dates du certificat
    // fullchain.pem
    //      notBefore=Jun 12 14:00:58 2020 GMT
    //      notAfter=Jun 10 14:00:58 2030 GMT
    // myCA.pem
    //      notBefore=Jun 12 14:00:56 2020 GMT
    //      notAfter=Jun 11 14:00:56 2025 GMT
    // L'EXPIRATION DES CRLS N'EST PAS PRISE EN COMPTE !
    // crl.pem
    //      lastUpdate=Jan 26 15:00:58 2021 GMT
    //      nextUpdate=Jan 24 15:00:58 2031 GMT

    /**
     * @dataProvider getWrongDate
     */

    public function testWrongDateIsTakenIntoAccount(DateTime $dateTime, string $message)
    {
        $verifyPKCS7Signature = new VerifyPKCS7Signature(
            __DIR__ . '/fixtures/signaturesPKCS7/ac',
            new VerifyPemCertificateFactory(),
            new PemCertificateFactory(),
            new OpenSSLWrapper(
                __DIR__ . '/fixtures/signaturesPKCS7/ac',
                new CommandLauncher(),
                new CheckSnInCRLFactory(new CRLReader())
            )
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage($message);
        $verifyPKCS7Signature->verifySignature(
            file_get_contents(__DIR__ . '/fixtures/signaturesPKCS7/test_pdf.pdf.p7s'),
            [],
            __DIR__ . '/fixtures/signaturesPKCS7/test_pdf.pdf',
            $dateTime
        );
    }

    /**
     * @throws Exception
     */
    public function getWrongDate(): array
    {
        return [
            [new DateTime('Jun 12 14:00:57 2020', new DateTimeZone('GMT')),
                "La date de la signature 12-Jun-2020 14:00:57 n'entre pas dans la date de validité du certificat 12-Jun-2020 16:00:58"
            ],
            [new DateTime('Jun 10 14:00:59 2030', new DateTimeZone('GMT')),
                "La date de la signature 10-Jun-2030 14:00:59 n'entre pas dans la date de validité du certificat 12-Jun-2020 16:00:58"
            ]
        ];
    }

    /**
     * @dataProvider getGoodDate
     * @throws Exception
     */

    public function testGoodDateIsTakenIntoAccount(DateTime $dateTime)
    {
        $baseSignatureDir = __DIR__ . '/fixtures/signaturesPKCS7';

        $verifyPKCS7Signature = new VerifyPKCS7Signature(
            __DIR__ . '/fixtures/signaturesPKCS7/ac',
            new VerifyPemCertificateFactory(),
            new PemCertificateFactory(),
            new OpenSSLWrapper(
                __DIR__ . '/fixtures/signaturesPKCS7/ac',
                new CommandLauncher(),
                new CheckSnInCRLFactory(new CRLReader())
            )
        );

        static::assertTrue(
            $verifyPKCS7Signature->verifySignature(
                file_get_contents("$baseSignatureDir/test_pdf.pdf.p7s"),
                [],
                "$baseSignatureDir/test_pdf.pdf",
                $dateTime
            )
        );
    }

    /**
     * @throws Exception
     */
    public function getGoodDate(): array
    {
        return [
            [new DateTime('Jan 26 15:00:58 2021', new DateTimeZone('GMT'))],// Debut de validité crl
            [new DateTime('Jun 11 14:00:55 2025', new DateTimeZone('GMT'))] // Fin de validité myCA.pem
        ];
    }

    /**
     * @throws Exception
     */
    public function testverifyCertificate()
    {
        $baseSignatureDir = __DIR__ . '/fixtures/signaturesPKCS7';
        $verificator = new VerifyPKCS7Signature(
            "$baseSignatureDir/ac/",
            new VerifyPemCertificateFactory(),
            new PemCertificateFactory(),
            new OpenSSLWrapper(
                "$baseSignatureDir/ac/",
                new CommandLauncher(),
                new CheckSnInCRLFactory(new CRLReader())
            )
        );

        $verificator->verifySignature(
            file_get_contents("$baseSignatureDir/test_pdf.pdf.p7s"),
            VerifyPemCertificate::CERTIFICATE_CHAIN_ERRORS,
            null,
            new DateTime('01-01-2025')
        );
        $this->addToAssertionCount(1);
    }
}
