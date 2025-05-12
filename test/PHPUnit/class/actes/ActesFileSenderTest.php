<?php

namespace PHPUnit\class\actes;

use Exception;
use PHPUnit\Framework\TestCase;
use S2lowLegacy\Class\actes\ActesFileSender;
use S2lowLegacy\Class\actes\ActesMinistereProperties;
use S2lowLegacy\Class\CurlWrapper;
use S2lowLegacy\Class\CurlWrapperFactory;
use S2lowLegacy\Lib\X509Certificate;

class ActesFileSenderTest extends TestCase
{
    protected function setUp(): void
    {
        $this->curlWrapperFactoryMock = $this->getMockBuilder(CurlWrapperFactory::class)
            ->disableOriginalConstructor()->getMock();
        $this->curlWrapperMock = $this->getMockBuilder(CurlWrapper::class)
            ->disableOriginalConstructor()->getMock();
        $this->curlWrapperFactoryMock->method('getNewInstance')
            ->willReturn($this->curlWrapperMock);

        $this->x509CertificateMock = $this->getMockBuilder(X509Certificate::class)
            ->disableOriginalConstructor()->getMock();

        $this->setProperties = [];
    }
    /**
     * @throws Exception
     */
    public function testSendMinistereSuccess(): void
    {
        $actesMinistereProperties = new ActesMinistereProperties(
            'https://128.127.126.125:9456/path/to/upload.php',
            ActesMinistereProperties::AUTHENTICATION_POST,
            'login',
            'password',
            'client_certificate',
            'client_certificate_key',
            'client_certificate_password',
            false,
            __DIR__ . '/../fixtures/toto.txt',
        );

        $actesFileSender = new ActesFileSender(
            $actesMinistereProperties,
            '/path/to/truststore',
            $this->curlWrapperFactoryMock,
            $this->x509CertificateMock
        );

        $this->curlWrapperMock->expects(self::once())->method('setClientCertificate')
            ->with(
                'client_certificate',
                'client_certificate_key',
                'client_certificate_password'
            );

        $this->mockSetPropertiesFunction();

        $this->curlWrapperMock->expects(self::once())->method('setTimeout')
            ->with(60, 60 * 3);

        $this->curlWrapperMock->expects(self::once())->method('addPostFile')
            ->with('filetosend.tar.gz', '/path/to/filetosend.tar.gz');
        $this->curlWrapperMock->expects(self::once())->method('get')
            ->with('https://128.127.126.125:9456/path/to/upload.php?user=login&password=password');

        $this->curlWrapperMock->method('getHTTPCode')->willReturn(200);

        static::assertTrue($actesFileSender->send('/path/to/filetosend.tar.gz'));

        $expectedParameters = [
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_CERTINFO => 1,
            CURLOPT_CAPATH => '/path/to/truststore',
        ];
        self::assertEqualsCanonicalizing($expectedParameters, $this->setProperties);
    }

    /**
     * @throws Exception
     */
    public function testSendMinistereFail(): void
    {
        $actesMinistereProperties = new ActesMinistereProperties(
            'https://128.127.126.125:9456/path/to/upload.php',
            ActesMinistereProperties::AUTHENTICATION_POST,
            'login',
            'password',
            'client_certificate',
            'client_certificate_key',
            'client_certificate_password',
            false,
            __DIR__ . '/../fixtures/toto.txt',
        );

        $actesFileSender = new ActesFileSender(
            $actesMinistereProperties,
            '/path/to/truststore',
            $this->curlWrapperFactoryMock,
            $this->x509CertificateMock
        );

        $this->curlWrapperMock->method('getHTTPCode')->willReturn(500);
        $this->curlWrapperMock->method('getLastError')->willReturn('Last Error');

        self::expectException(Exception::class);
        self::expectExceptionMessage('Last Error');
        static::assertTrue($actesFileSender->send('/path/to/filetosend.tar.gz'));
    }

    /**
     * @throws Exception
     */
    public function testSendSimulateurSuccess(): void
    {
        $actesMinistereProperties = new ActesMinistereProperties(
            'http://simulateur/Simulateur/actesPost',
            ActesMinistereProperties::AUTHENTICATION_NONE,
            '',
            '',
            '',
            '',
            '',
            false,
            __DIR__ . '/../fixtures/toto.txt',
        );

        $actesFileSender = new ActesFileSender(
            $actesMinistereProperties,
            '/path/to/truststore',
            $this->curlWrapperFactoryMock,
            $this->x509CertificateMock
        );

        $this->curlWrapperMock->expects(self::once())->method('setClientCertificate')
            ->with('', '', '');

        $this->mockSetPropertiesFunction();

        $this->curlWrapperMock->expects(self::once())->method('setTimeout')
            ->with(60, 60 * 3);

        $this->curlWrapperMock->expects(self::once())->method('addPostFile')
            ->with('filetosend.tar.gz', '/path/to/filetosend.tar.gz');
        $this->curlWrapperMock->expects(self::once())->method('get')
            ->with('http://simulateur/Simulateur/actesPost');

        $this->curlWrapperMock->method('getHTTPCode')->willReturn(200);

        static::assertTrue($actesFileSender->send('/path/to/filetosend.tar.gz'));

        $expectedParameters = [ ];

        self::assertEqualsCanonicalizing($expectedParameters, $this->setProperties);
    }

    private function mockSetPropertiesFunction(): void
    {
        $setProperties = &$this->setProperties;
        $callback = function ($properties, $values) use (&$setProperties) {
            $setProperties[$properties] = $values;
        };

        $this->curlWrapperMock
            ->method('setProperties')
            ->willReturnCallback($callback);
    }
}
