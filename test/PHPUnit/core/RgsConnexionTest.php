<?php

use IntegrationTests\S2lowIntegrationTestCase;
use S2lowLegacy\Class\RgsConnexion;

class RgsConnexionTest extends S2lowIntegrationTestCase
{
    private const SSL_CLIENT_VERIFY = 'SSL_CLIENT_VERIFY';
    private const SUCCESS = "SUCCESS";
    private const SSL_CLIENT_CERT = 'SSL_CLIENT_CERT';
    private const SSL_CLIENT_CERT_CHAIN_0 = 'SSL_CLIENT_CERT_CHAIN_0';
    private const SSL_CLIENT_CERT_CHAIN_1 = 'SSL_CLIENT_CERT_CHAIN_1';

    /**
     * @var RgsConnexion
     */
    private $rgsConnexion;

    public function setUp(): void
    {
        parent::setUp();
        $this->rgsConnexion = self::getContainer()->get(RgsConnexion::class);
    }

    public function testIsRgsConnexion()
    {
        $server = [];

        $rgsConnexion = new RgsConnexion(
            self::getContainer()->getParameter('app.openssl_path'),
            self::getContainer()->getParameter('app.path_to_rgs_valid_ca'),
        );
        $rgsConnexion->setServerGlobal($server);
        $this->assertFalse($rgsConnexion->isRgsConnexion());
    }

    public function testIsRgsConnexionNotVerify()
    {
        $server[self::SSL_CLIENT_VERIFY] = "ERROR";
        $this->rgsConnexion->setServerGlobal($server);
        $this->assertFalse($this->rgsConnexion->isRgsConnexion());
    }

    public function testIsRgsConnexionBadCertif()
    {
        $server[self::SSL_CLIENT_VERIFY] = self::SUCCESS;
        $server[self::SSL_CLIENT_CERT] = "rogue certificate";
        $this->rgsConnexion->setServerGlobal($server);
        $this->assertFalse($this->rgsConnexion->isRgsConnexion());
        $this->assertMatchesRegularExpression(
            "#Could not find certificate file#",
            $this->rgsConnexion->getLastMessage()
        );
    }

    public function testIsRgsConnexionOK()
    {
        $server[self::SSL_CLIENT_VERIFY] = self::SUCCESS;
        $server[self::SSL_CLIENT_CERT] = file_get_contents(__DIR__ . "/../controller/fixtures/contact@example.org.pem");
        $server[self::SSL_CLIENT_CERT_CHAIN_0] = file_get_contents(__DIR__ . "/../controller/fixtures/AC_LIBRICIEL_PERSONNEL_G2_CHAIN.pem");
        $this->rgsConnexion->setServerGlobal($server);
        $this->rgsConnexion->setRgsValidCaPath(__DIR__ . "/../controller/fixtures/validcaLibriciel");
        $this->assertTrue($this->rgsConnexion->isRgsConnexion());
    }

    public function testIsRgsConnexionAutosignedRoot()
    {
        $server[self::SSL_CLIENT_VERIFY] = self::SUCCESS;
        $server[self::SSL_CLIENT_CERT] = file_get_contents(__DIR__ . "/fixtures/CertAutosignedRoot/s2low-test-u.pem");
        $server[self::SSL_CLIENT_CERT_CHAIN_0] = file_get_contents(__DIR__ . "/fixtures/CertAutosignedRoot/AC_LIBRICIEL_RACINE_G1_CHAIN.pem");
        $server[self::SSL_CLIENT_CERT_CHAIN_1] = file_get_contents(__DIR__ . "/fixtures/CertAutosignedRoot/AC_LIBRICIEL_PERSONNEL_G2_CHAIN.pem");
        $this->rgsConnexion->setServerGlobal($server);
        $this->rgsConnexion->setRgsValidCaPath(__DIR__ . "/../controller/fixtures/validca");
        $this->assertFalse($this->rgsConnexion->isRgsConnexion());
    }

    /*public function testIsRgsConnexionAutosignedRootInCA()
    {
        $server[self::SSL_CLIENT_VERIFY] = self::SUCCESS;
        $server[self::SSL_CLIENT_CERT] = file_get_contents(__DIR__ . "/fixtures/CertAutosignedRoot/s2low-test-u.pem");
        $server[self::SSL_CLIENT_CERT_CHAIN_0] = file_get_contents(__DIR__ . "/fixtures/CertAutosignedRoot/AC_LIBRICIEL_RACINE_G1_CHAIN.pem");
        $server[self::SSL_CLIENT_CERT_CHAIN_1] = file_get_contents(__DIR__ . "/fixtures/CertAutosignedRoot/AC_LIBRICIEL_PERSONNEL_G2_CHAIN.pem");
        $this->rgsConnexion->setServerGlobal($server);
        $this->rgsConnexion->setRgsValidCaPath(__DIR__ . "/fixtures/CertAutosignedRoot/CA/");
        //$this->assertTrue($this->rgsConnexion->isRgsConnexion());
    }*/

    public function testGetClientCertChain()
    {
        $server[self::SSL_CLIENT_CERT_CHAIN_0] = "a";
        $server[self::SSL_CLIENT_CERT_CHAIN_1] = "b";
        $server['SSL_CLIENT_CERT_CHAIN_2'] = "c";
        $server['SSL_CLIENT_CERT_CHAIN_3'] = "d";
        $server['SSL_CLIENT_CERT_CHAIN_4'] = "e";
        $this->rgsConnexion->setServerGlobal($server);
        $expected = "a\nb\nc\nd\ne\n";
        $this->assertEquals($expected, $this->rgsConnexion->getClientCertChain());
    }
}
