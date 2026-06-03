<?php

namespace S2low\Tests\Services;

use PHPUnit\Framework\TestCase;
use S2low\Exceptions\CertificateException;
use S2low\Services\UserCertificateService;
use S2lowLegacy\Lib\X509Certificate;
use S2lowLegacy\Model\UserSQL;
use Symfony\Component\Clock\MockClock;

class UserCertificateServiceTest extends TestCase
{
    private const VALID_PEM = "-----BEGIN CERTIFICATE-----\n" .
        "MIICXjCCAccCAQQwDQYJKoZIhvcNAQEFBQAwdDELMAkGA1UEBhMCRlIxDzANBgNV\n" .
        "BAgTBkZyYW5jZTEPMA0GA1UEBxMGRWN1bGx5MRgwFgYDVQQKEw9BbHRlcm5hbmNl\n" .
        "IFNvZnQxKTAnBgNVBAMTIENBIEFsdGVybmFuY2UgU29mdCAtIHRlZGV0aXMgREVW\n" .
        "MB4XDTA4MDIyMTE0NTY1NFoXDTE4MDIxODE0NTY1NFowezELMAkGA1UEBhMCRlIx\n" .
        "DzANBgNVBAgTBkZyYW5jZTEPMA0GA1UEBxMGRWN1bGx5MR4wHAYDVQQKExVUZWRl\n" .
        "dGlzIERldmVsb3BwZW1lbnQxKjAoBgNVBAMTIVV0aWxpc2F0ZXVyIHRlZGV0aXMg\n" .
        "ZGV2ZWxvcHBlbWVudDCBnzANBgkqhkiG9w0BAQEFAAOBjQAwgYkCgYEAysI5SqQs\n" .
        "ImlVvaedEhEr3qht2AOaGRnfGxCjREpvqTtMyhGFyfmKWL9wuw55/g9xI5lNdbtJ\n" .
        "7bVGR4u5pigt5Tx84EZBi4tVNar5rmwa3uc8S1hxo5GLbtVvea1Znk8q0RSvgNI4\n" .
        "cBRUgKW5eTlTlFOsdxTe5B7ymayNz82hSx0CAwEAATANBgkqhkiG9w0BAQUFAAOB\n" .
        "gQBXs6nGCqdtGHwfsekzSSBgEbvAEUOF5iP/p/CKnwNXEv0QeOUq3jRlci4ehK5r\n" .
        "Ez0XTPA8qbECVa3ESDSwzirnfN6SO/wLq1jFIzrjur2QLIBAfFCzlUwALDL3pzG3\n" .
        "KNTAvoMIEgudIm/6me1kwqO8UxzKInYG8m/TW3bWCF6F1A==\n" .
        "-----END CERTIFICATE-----";

    private UserSQL|\PHPUnit\Framework\MockObject\MockObject $userSQLMock;
    private X509Certificate $x509Certificate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userSQLMock = $this->createMock(UserSQL::class);
        $this->x509Certificate = new X509Certificate();
    }

    /**
     * @dataProvider validCertificateExpirationCasesProvider
     */
    public function testGetNbDaysBeforeUserCertificatExpire(string $clockTime, int $expectedDays): void
    {
        $userId = 123;

        $this->userSQLMock->expects($this->once())
            ->method('getUserCertificate')
            ->with($userId)
            ->willReturn(self::VALID_PEM);

        $clock = new MockClock($clockTime);
        $service = new UserCertificateService(
            $this->userSQLMock,
            $this->x509Certificate,
            $clock
        );

        $result = $service->getNbDaysBeforeUserCertificatExpire($userId);

        $this->assertSame($expectedDays, $result);
    }

    public function testGetNbDaysBeforeUserCertificatExpireThrowsCertificateExceptionOnInvalidPem(): void
    {
        $userId = 123;
        $invalidPem = 'invalid_pem_content';

        $this->userSQLMock->method('getUserCertificate')->willReturn($invalidPem);

        $clock = new MockClock('2018-02-08 13:56:54');

        $service = new UserCertificateService(
            $this->userSQLMock,
            $this->x509Certificate,
            $clock
        );

        $this->expectException(CertificateException::class);
        $this->expectExceptionMessage("Impossible de lire le certificat");

        $service->getNbDaysBeforeUserCertificatExpire($userId);
    }

    public function validCertificateExpirationCasesProvider(): array
    {
        return [
            '10 jours et 1 heure avant expiration' => ['2018-02-08 13:56:54', 10],
            '5 jours et 1 heure avant expiration' => ['2018-02-13 13:56:54', 5],
            '1 heure avant expiration' => ['2018-02-18 13:56:54', 0],
            '1 seconde avant expiration' => ['2018-02-18 14:56:53', 0],
            'date exacte d\'expiration' => ['2018-02-18 14:56:54', 0],
            '1 seconde après expiration' => ['2018-02-18 14:56:55', -1],
            '1 jour après expiration' => ['2018-02-19 14:56:54', -1],
            '5 jours après expiration' => ['2018-02-23 14:56:54', -5],
        ];
    }
}
