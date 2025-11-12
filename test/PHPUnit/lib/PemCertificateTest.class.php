<?php

declare(strict_types=1);

use S2lowLegacy\Lib\PemCertificateFactory;

class PemCertificateTest extends S2lowTestCase
{
    public const BASE_CERTIFICATES_DIR = __DIR__ . '/../class/fixtures/certificats';

    public function testAnExpiredCertificate()
    {
        $factory = new PemCertificateFactory();
        $certificate = $factory->getFromString(
            file_get_contents(self::BASE_CERTIFICATES_DIR . '/dateKo/fullchain.pem')
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/La date de la signature .*? n'entre pas dans la date de validité du certificat .*? - .*?/");
        $certificate->checkCertificateIsValidAtDate(new DateTime());
    }

    public function testAnOkCertificate()
    {
        $factory = new PemCertificateFactory();
        $certificate = $factory->getFromString(
            file_get_contents(self::BASE_CERTIFICATES_DIR . '/dateOk/fullchain.pem')
        );

        $this->expectNotToPerformAssertions();
        $certificate->checkCertificateIsValidAtDate(new DateTime());
    }

    public function testAnOkMinimalCertificateWithLineBreaks()
    {
        $factory = new PemCertificateFactory();
        $certificate = $factory->getFromMinimalString(
            file_get_contents(self::BASE_CERTIFICATES_DIR . '/dateOk/MinimalFullChainWithLineBreaks.pem')
        );

        $this->expectNotToPerformAssertions();
        $certificate->checkCertificateIsValidAtDate(new DateTime());
    }

    public function testGetSubject()
    {
        $factory = new PemCertificateFactory();
        $certificate = $factory->getFromString(
            file_get_contents(self::BASE_CERTIFICATES_DIR . '/dateOk/fullchain.pem')
        );

        self::assertEquals(
            ['C' => 'FR',
                'ST' => 'HERAULT',
                'L' => 'MONTPELLIER',
                'O' => 'LIBRICIEL',
                'OU' => 'CERTIFICAT_AUTO_SIGNE',
                'CN' => 'truc',
                'emailAddress' => 'test@localhost'],
            $certificate->getSubjectDN()
        );
    }

    public function testGetIssuerDN()
    {
        $factory = new PemCertificateFactory();
        $certificate = $factory->getFromString(
            file_get_contents(self::BASE_CERTIFICATES_DIR . '/dateOk/fullchain.pem')
        );

        self::assertEquals(
            [
                'emailAddress' => 'test@localhost',
                'CN' => 'DOCKER CA',
                'OU' => 'CERTIFICAT_AUTO_SIGNE',
                'O' => 'LIBRICIEL',
                'L' => 'MONTPELLIER',
                'ST' => 'HERAULT',
                'C' => 'FR'
            ],
            $certificate->getIssuerDN()
        );
    }

    public function testIssuerAndSubjectAreTheSame()
    {
        $factory = new PemCertificateFactory();
        $certificate = $factory->getFromString(
            file_get_contents(self::BASE_CERTIFICATES_DIR . '/dateOk/ac/myCA.pem')
        );

        self::assertEquals(
            $certificate->getSubjectDN(),
            $certificate->getIssuerDN()
        );
    }

    /**
     * @dataProvider certsProvider
     */
    public function testCertificateIsAutosigned(string $relativePath, bool $isAutosigned)
    {
        $factory = new PemCertificateFactory();
        $certificate = $factory->getFromString(
            file_get_contents(self::BASE_CERTIFICATES_DIR . "/$relativePath")
        );

        self::assertEquals(
            $isAutosigned,
            $certificate->isAutosigned()
        );
    }

    public function certsProvider()
    {
        return [
            ['/dateOk/fullchain.pem', false],
            ['/dateOk/ac/myCA.pem', true]
        ];
    }
}
