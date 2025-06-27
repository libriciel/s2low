<?php

namespace PHPUnit\lib;

use PHPUnit\Framework\TestCase;
use S2lowLegacy\Lib\CertificateChain;
use S2lowLegacy\Lib\CertificateChainsBuilder;
use S2lowLegacy\Lib\PemCertificate;
use S2lowLegacy\Lib\PemCertificateFactory;

class CertificateChainBuilderTest extends TestCase
{
    public const BASE_CERTIFICATES_DIR = __DIR__ . '/../core/fixtures/CertAutosignedRoot/';
    private PemCertificate $x509_pem_certificate;
    private PemCertificate $x509_ca_certificate;
    private PemCertificate $x509_intermediate_certificate;
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

    public function testBuild()
    {
        $certificateChainBuilder = new CertificateChainsBuilder();
        /** @var CertificateChain[] $certificateChains */
        $certificateChains = $certificateChainBuilder->build(
            $this->x509_pem_certificate,
            $this->x509_ca_certificate,
            $this->x509_intermediate_certificate
        );
        self::assertTrue($certificateChains[0]->hasValidPathToRoot());
    }

    /**
     * @throws \PHPUnit\lib\CertificateChainException
     */
    public function testBuild2()
    {
        $certificateChainBuilder = new CertificateChainsBuilder();
        /** @var CertificateChain[] $certificateChains */
        $certificateChains = $certificateChainBuilder->build(
            $this->x509_pem_certificate,
            $this->x509_ca_certificate
        );
        // Il y a deux chaines de certificats :
        //  -> x509_pem_certificate n'a pas de path to root valide
        //  -> x509_ca_certificate a un path to root valide
        self::assertEquals(2, count($certificateChains));
        self::assertFalse($certificateChains[0]->hasValidPathToRoot());
        self::assertTrue($certificateChains[1]->hasValidPathToRoot());
    }
}
