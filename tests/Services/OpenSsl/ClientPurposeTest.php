<?php

namespace S2low\Tests\Services\OpenSsl;

use PHPUnit\Framework\TestCase;
use RgsCertificateTest;
use S2low\Services\OpenSsl\ClientPurpose;

class ClientPurposeTest extends TestCase
{
    private ClientPurpose $clientPurpose;

    public function setUp(): void
    {
        $this->clientPurpose = new ClientPurpose();
        parent::setUp();
    }
    public function testIsSslClient()
    {
        $certificateWithSslClientPurpose = file_get_contents(RgsCertificateTest::CERTIFICATE_1);
        static::assertTrue($this->clientPurpose->check($certificateWithSslClientPurpose, RgsCertificateTest::VALIDCA_PATH));
    }

    public function testIsNotSslClient()
    {
        $certificateWithoutSslClientPurpose = file_get_contents(
            RgsCertificateTest::CERTIFICATE_WITHOUT_CLIENT_PURPOSE
        );
        static::assertFalse($this->clientPurpose->check($certificateWithoutSslClientPurpose, RgsCertificateTest::VALIDCA_PATH));
    }

    public function testIsNotEvenCertificate()
    {
        static::assertFalse($this->clientPurpose->check('Not a certificate', RgsCertificateTest::VALIDCA_PATH));
    }
}
