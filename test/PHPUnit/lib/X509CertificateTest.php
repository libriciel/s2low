<?php

class X509CertificateTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var X509Certificate
     */
    private $x509Certificate;

    public function setUp(): void
    {
        parent::setUp();
        $_SERVER['SSL_CLIENT_VERIFY'] = false;
        $this->x509Certificate = new X509Certificate();
    }

    public function testPemCleaning()
    {
        $not_clean_pem = file_get_contents(__DIR__ . "/fixtures/pem_with_text.pem");
        $clean_pem = $this->x509Certificate->pemClean($not_clean_pem);
        $this->assertEquals(file_get_contents(__DIR__ . "/fixtures/clean_pem.pem"), $clean_pem);
    }


    public function testPemCleaningBadData()
    {
        $this->setExpectedException("Exception", "Impossible de lire le certificat");
        $this->x509Certificate->pemClean("not a pem file");
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
        $this->setExpectedException("Exception", "Impossible de lire le certificat");
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
