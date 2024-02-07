<?php

declare(strict_types=1);

namespace PHPUnit\lib;

use DateTime;
use Exception;
use PHPUnit\S2lowTestCase;
use S2lowLegacy\Lib\PemCertificateFactory;

class PemCertificateTest extends S2lowTestCase
{
    public const BASE_CERTIFICATES_DIR = __DIR__ . "/../class/fixtures/certificats";

    public function testAnExpiredCertificate()
    {
        $factory = new PemCertificateFactory();
        $certificate = $factory->getFromString(
            file_get_contents(self::BASE_CERTIFICATES_DIR . "/dateKo/fullchain.pem")
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/La date de la signature .*? n'entre pas dans la date de validité du certificat .*? - .*?/");
        $certificate->checkCertificateIsValidAtDate(new DateTime());
    }

    public function testAnOkCertificate()
    {
        $factory = new PemCertificateFactory();
        $certificate = $factory->getFromString(
            file_get_contents(self::BASE_CERTIFICATES_DIR . "/dateOk/fullchain.pem")
        );

        $this->expectNotToPerformAssertions();
        $certificate->checkCertificateIsValidAtDate(new DateTime());
    }

    public function testAnOkMinimalCertificateWithLineBreaks()
    {
        $factory = new PemCertificateFactory();
        $certificate = $factory->getFromMinimalString(
            file_get_contents(self::BASE_CERTIFICATES_DIR . "/dateOk/MinimalFullChainWithLineBreaks.pem")
        );

        $this->expectNotToPerformAssertions();
        $certificate->checkCertificateIsValidAtDate(new DateTime());
    }
}
