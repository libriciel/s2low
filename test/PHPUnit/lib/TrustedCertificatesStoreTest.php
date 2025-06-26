<?php

namespace PHPUnit\lib;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use S2lowLegacy\Lib\PemCertificateFactory;
use S2lowLegacy\Lib\TrustedCertificatesStore;

class TrustedCertificatesStoreTest extends TestCase
{
    public const BASE_CERTIFICATES_DIR = __DIR__ . '/../core/fixtures/CertAutosignedRoot/';
    public function testGetAvailableCertificates()
    {
        $trustedCertificateStore = new TrustedCertificatesStore(
            self::BASE_CERTIFICATES_DIR,
            new PemCertificateFactory(),
            $this->getMockBuilder(LoggerInterface::class)->getMock()
        );

        $trustedCertificates = $trustedCertificateStore->getAvailableCertificates();

        self::assertCount(3, $trustedCertificates);
    }
}
