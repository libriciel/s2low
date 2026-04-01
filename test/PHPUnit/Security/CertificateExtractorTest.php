<?php

namespace Test\PHPUnit\Security;

use PHPUnit\Framework\TestCase;
use S2low\Security\CertificateExtractor;
use S2low\Security\Exceptions\CertificateExtractionException;
use S2lowLegacy\Lib\X509Certificate;
use Symfony\Component\HttpFoundation\Request;

class CertificateExtractorTest extends TestCase
{
    private function createExtractor($x509Mock = null): CertificateExtractor
    {
        if (!$x509Mock) {
            $x509Mock = $this->createMock(X509Certificate::class);
        }
        return new CertificateExtractor($x509Mock);
    }

    public function testHasValidCertificateReturnsFalseIfVerifyFails(): void
    {
        $request = new Request([], [], [], [], [], ['SSL_CLIENT_VERIFY' => 'FAILED', 'SSL_CLIENT_CERT' => 'cert']);
        $extractor = $this->createExtractor();
        $this->assertFalse($extractor->hasValidCertificate($request));
    }

    public function testHasValidCertificateReturnsFalseIfNoCert(): void
    {
        $request = new Request([], [], [], [], [], ['SSL_CLIENT_VERIFY' => 'SUCCESS']);
        $extractor = $this->createExtractor();
        $this->assertFalse($extractor->hasValidCertificate($request));
    }

    public function testHasValidCertificateReturnsTrue(): void
    {
        $request = new Request([], [], [], [], [], ['SSL_CLIENT_VERIFY' => 'SUCCESS', 'SSL_CLIENT_CERT' => 'cert']);
        $extractor = $this->createExtractor();
        $this->assertTrue($extractor->hasValidCertificate($request));
    }

    public function testExtractReturnsNullIfInvalid(): void
    {
        $request = new Request();
        $extractor = $this->createExtractor();
        $this->assertNull($extractor->extract($request));
    }

    public function testExtractReturnsNullIfX509Fails(): void
    {
        $request = new Request([], [], [], [], [], ['SSL_CLIENT_VERIFY' => 'SUCCESS', 'SSL_CLIENT_CERT' => 'cert']);
        $x509Mock = $this->createMock(X509Certificate::class);
        $x509Mock->method('getInfo')->willReturn(false);
        $extractor = $this->createExtractor($x509Mock);
        $this->assertNull($extractor->extract($request));
    }

    public function testExtractReturnsArray(): void
    {
        $request = new Request([], [], [], [], [], ['SSL_CLIENT_VERIFY' => 'SUCCESS', 'SSL_CLIENT_CERT' => 'cert']);
        $x509Mock = $this->createMock(X509Certificate::class);
        $x509Mock->method('getInfo')->willReturn([
            'subject_name' => 'subject',
            'issuer_name' => 'issuer',
            'certificate_hash' => 'hash'
        ]);
        $extractor = $this->createExtractor($x509Mock);
        $result = $extractor->extract($request);

        $this->assertEquals([
            'ssl_client_verify' => 'SUCCESS',
            'subject_dn' => 'subject',
            'issuer_dn' => 'issuer',
            'certificate_hash' => 'hash',
            'ssl_client_cert' => 'cert',
        ], $result);
    }

    public function testExtractCertificateOrFailThrowsOnNull(): void
    {
        $request = new Request();
        $extractor = $this->createExtractor();
        $this->expectException(CertificateExtractionException::class);
        $this->expectExceptionMessage('Aucun certificat trouvé.');
        $extractor->extractCertificateOrFail($request);
    }

    public function testExtractCertificateOrFailThrowsOnException(): void
    {
        $request = new Request([], [], [], [], [], ['SSL_CLIENT_VERIFY' => 'SUCCESS', 'SSL_CLIENT_CERT' => 'cert']);
        $x509Mock = $this->createMock(X509Certificate::class);
        $x509Mock->method('getInfo')->willThrowException(new \Exception('Erreur X509'));
        $extractor = $this->createExtractor($x509Mock);

        $this->expectException(CertificateExtractionException::class);
        $this->expectExceptionMessage('Une erreur est survenu pendant la lecture du certificat : Erreur X509');
        $extractor->extractCertificateOrFail($request);
    }
}
