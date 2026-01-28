<?php

use S2lowLegacy\Lib\X509Certificate;

class X509CertificateTest extends S2lowTestCase
{
    private $serverSave;
    private X509Certificate $x509Certificate;

    public function setUp(): void
    {
        parent::setUp();
        $this->serverSave = $_SERVER;
        $_SERVER = [];
        $_SERVER['SSL_CLIENT_VERIFY'] = false;
        $this->x509Certificate = new X509Certificate();
    }

    public function tearDown(): void
    {
        $_SERVER = $this->serverSave;
        parent::tearDown();
    }

    public function testRetrieveClientInfo()
    {
        $_SERVER['SSL_CLIENT_VERIFY'] = false;
        $this->assertFalse($this->x509Certificate->retrieveClientInfo());
    }

    public function testRetrieveClientInfoFailed()
    {
        $_SERVER['SSL_CLIENT_VERIFY'] = "FAILED";
        $this->assertFalse($this->x509Certificate->retrieveClientInfo());
    }

    public function testRetrieveClientInfoWithEmptyCert()
    {
//        $_SERVER = [];
        $_SERVER['SSL_CLIENT_VERIFY'] = "SUCCESS";
        $info = $this->x509Certificate->retrieveClientInfo();
        $this->assertFalse($info);
    }

    public function testRetrieveClientInfoWithCert()
    {
        $_SERVER['SSL_CLIENT_VERIFY'] = "SUCCESS";
        $_SERVER['SSL_CLIENT_S_DN'] = "test_subject";
        $_SERVER['SSL_CLIENT_I_DN'] = "test_issuer";
        $_SERVER['SSL_CLIENT_CERT'] = file_get_contents(__DIR__ . "/fixtures/clean_pem.pem");
        $info = $this->x509Certificate->retrieveClientInfo();
        $this->assertEquals(
            '/C=FR/ST=France/L=Lyon/O=Sigmalis/CN=Sigmalis Certificate Autority/emailAddress=eric@sigmalis.com',
            $info['issuer']
        );
    }

    public function testRetrieveClientInfoWithBadCert()
    {
        $_SERVER['SSL_CLIENT_VERIFY'] = "SUCCESS";
        $_SERVER['SSL_CLIENT_S_DN'] = "test_subject";
        $_SERVER['SSL_CLIENT_I_DN'] = "test_issuer";
        $_SERVER['SSL_CLIENT_CERT'] = "foo";
        $this->assertFalse($this->x509Certificate->retrieveClientInfo());
    }

    public function testGetExpirationDate()
    {
        $info = $this->x509Certificate->getExpirationDate(file_get_contents(__DIR__ . "/fixtures/clean_pem.pem"));
        $this->assertEquals("2025-08-16 10:33:59", $info);
    }

    public function testGetExpirationDateFailed()
    {
        $this->assertFalse($this->x509Certificate->getExpirationDate(false));
    }

    public function testGetInfo()
    {
        $info = $this->x509Certificate->getInfo(file_get_contents(__DIR__ . "/fixtures/clean_pem.pem"));
        $this->assertEquals("2025-08-16 10:33:59", $info['expiration_date']);
    }

    public function testGetInfoFailed()
    {
        $this->assertFalse($this->x509Certificate->getInfo(false));
    }

    public function testGetInfoFailed2()
    {
        $this->expectException("Exception");
        $this->expectExceptionMessage("Impossible de lire le certificat");
        $this->x509Certificate->getInfo("toto");
    }

    public function testGetBase64Hash()
    {
        $this->assertEquals(
            "ieQoLUcitdU9iZIJLPoIdp8TcUY=",
            $this->x509Certificate->getBase64Hash(file_get_contents(__DIR__ . "/fixtures/clean_pem.pem"), 'sha1')
        );
    }

    public function testGetIssuerDN()
    {
        $this->assertEquals(
            "emailAddress=eric@sigmalis.com, CN=Sigmalis Certificate Autority, O=Sigmalis, L=Lyon, ST=France, C=FR",
            $this->x509Certificate->getIssuerDN(file_get_contents(__DIR__ . "/fixtures/clean_pem.pem"))
        );
    }
}
