<?php

namespace PHPUnit\lib;

use Exception;
use PHPUnit\Framework\TestCase;
use S2lowLegacy\Lib\CertificateChain;
use S2lowLegacy\Lib\PemCertificateFactory;

class CertificateChainTest extends TestCase
{
    public const BASE_CERTIFICATES_DIR = __DIR__ . '/../core/fixtures/CertAutosignedRoot/';
    private \S2lowLegacy\Lib\PemCertificate $x509_pem_certificate;
    private \S2lowLegacy\Lib\PemCertificate $x509_ca_certificate;
    private \S2lowLegacy\Lib\PemCertificate $x509_intermediate_certificate;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();
        $factory = new PemCertificateFactory();
        $this->x509_pem_certificate = $factory->getFromString(
            file_get_contents(self::BASE_CERTIFICATES_DIR . 's2low-test-u.pem')
        );
        $this->x509_ca_certificate = $factory->getFromString(
            file_get_contents(self::BASE_CERTIFICATES_DIR . 'AC_LIBRICIEL_RACINE_G1_CHAIN.pem')
        );
        $this->x509_intermediate_certificate = $factory->getFromString(
            file_get_contents(self::BASE_CERTIFICATES_DIR . 'AC_LIBRICIEL_PERSONNEL_G2_CHAIN.pem')
        );
    }

    public function testHasValidPathToRoot()
    {
        $certificateChain = new CertificateChain(
            $this->x509_pem_certificate,
            $this->x509_intermediate_certificate,
            $this->x509_ca_certificate
        );
        self::assertTrue($certificateChain->hasValidPathToRoot());
    }

    public function testHasValidPathToRootBrokenPath()
    {
        $certificateChain = new CertificateChain(
            $this->x509_pem_certificate,
            $this->x509_ca_certificate
        );
        self::assertFalse($certificateChain->hasValidPathToRoot());
    }

    public function testHasValidPathToRootNoAutosignedAtRoot()
    {
        $certificateChain = new CertificateChain(
            $this->x509_pem_certificate,
            $this->x509_intermediate_certificate,
        );
        self::assertFalse($certificateChain->hasValidPathToRoot());
    }
}
