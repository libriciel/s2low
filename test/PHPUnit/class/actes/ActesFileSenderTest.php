<?php

namespace PHPUnit\class\actes;

use PHPUnit\Framework\TestCase;
use S2lowLegacy\Class\actes\ActesFileSender;
use S2lowLegacy\Class\actes\ActesMinistereProperties;
use S2lowLegacy\Class\CurlWrapper;
use S2lowLegacy\Class\CurlWrapperFactory;
use S2lowLegacy\Lib\X509Certificate;

class ActesFileSenderTest extends TestCase
{
    public function testSendMinistereSuccess()
    {
        $curlWrapperFactoryMock = $this->getMockBuilder(CurlWrapperFactory::class)
            ->disableOriginalConstructor()->getMock();
        $curlWrapperMock = $this->getMockBuilder(CurlWrapper::class)
            ->disableOriginalConstructor()->getMock();
        $curlWrapperFactoryMock->method('getNewInstance')->willReturn($curlWrapperMock);

        $x509CertificateMock = $this->getMockBuilder(X509Certificate::class)
            ->disableOriginalConstructor()->getMock();

        $actesMinistereProperties = new ActesMinistereProperties(
            'https://128.127.126.125:9456/path/to/upload.php',
            ActesMinistereProperties::AUTHENTICATION_POST,
            'login',
            'password',
            'client_certificate',
            'client_certificate_key',
            'client_certificate_password',
            true,
            __DIR__ . '/../fixtures/toto.txt',
        );

        $actesFileSender = new ActesFileSender(
            $actesMinistereProperties,
            '/path/to/truststore',
            $curlWrapperFactoryMock,
            $x509CertificateMock
        );

        $curlWrapperMock->expects(self::once())->method('setClientCertificate')
            ->with(
                'client_certificate',
                'client_certificate_key',
                'client_certificate_password'
            );

        $curlWrapperMock->expects(self::once())->method('setTimeout')->with(60, 60 * 3);

        $curlWrapperMock->expects(self::once())->method('addPostFile')
            ->with('filetosend.tar.gz', '/path/to/filetosend.tar.gz');
        $curlWrapperMock->expects(self::once())->method('get')
            ->with('https://128.127.126.125:9456/path/to/upload.php?user=login&password=password');

        $curlWrapperMock->method('getHTTPCode')->willReturn(200);

        $this->assertTrue($actesFileSender->send('/path/to/filetosend.tar.gz'));
    }
}
