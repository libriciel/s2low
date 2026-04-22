<?php

use PHPUnit\Framework\MockObject\MockObject;
use S2lowLegacy\Class\actes\ActesFileSender;
use S2lowLegacy\Class\actes\ActesMinistereProperties;
use S2lowLegacy\Class\CurlWrapper;
use S2lowLegacy\Class\CurlWrapperFactory;

class ActesFileSenderTest extends S2lowTestCase
{
    private MockObject|CurlWrapperFactory $curlWrapperFactory;
    private string $tempCertificatePath;

    private string $dummyCertificate = '-----BEGIN CERTIFICATE-----
MIIDBzCCAe+gAwIBAgIUTrqi4d/GaZZajzEPSeeFnzG6/UkwDQYJKoZIhvcNAQEL
BQAwEzERMA8GA1UEAwwIVGVzdENlcnQwHhcNMjYwNDIyMDk0MjMyWhcNMjcwNDIy
MDk0MjMyWjATMREwDwYDVQQDDAhUZXN0Q2VydDCCASIwDQYJKoZIhvcNAQEBBQAD
ggEPADCCAQoCggEBALkc+TYdXWeqWRAvTqb0vIRfig72rQe16jYmGJNmcK0ywqoF
pxIq/7PBADaL5cKZ1aB9uZNW5w2NpEmdFrZ4Ix50z10FuZHBn1tlKwK5Z62KEhv6
ERuPlOcN8be4Xk95b+kKVGIt/uC4HnYabNQh6yrwrDmzuFST5MNYVwtxciLmKQzR
+09h1qS45cbgWMl3VbsL921VIS7UHeoEZYlxPLSSjEQut1ZfIpSia8gc/zOKhM+I
RPTKp+Kt0sV2ByC3AISO5vvJUFe42j73kdR8K+LNIq3CJKK659Nka22qpjpNHuu9
+Gseh2k3ej/7Ep7Ag2xBiqXSh75WIPCc1dbZR3cCAwEAAaNTMFEwHQYDVR0OBBYE
FKkf8yjGh22cyJqOvIjHDejUhc3WMB8GA1UdIwQYMBaAFKkf8yjGh22cyJqOvIjH
DejUhc3WMA8GA1UdEwEB/wQFMAMBAf8wDQYJKoZIhvcNAQELBQADggEBADncPzzJ
+ZmIrZWmg+an9GECaQbB3264fHPuMKeS4uiB35FkVmUqr7+NkPROk3ugXHdUysu/
ODegbLTaeyx07WfokciqzA6i/3FITTyCDXOquTRt6Dy4zGM+OyXnNrfpTZj1ueyl
VfFHVGhdgmeb1tcsDhdicpaLKuUx+V5LAE94B1wdG2bRZFdMDswARxrbOc8lIu9M
OIf60psGE8/Y5gJVpraLtEpEbn2ip5lTVaClO3DcqjnmPw2WxD9VYMWws1igyTEf
13zibkIaRHU3YPbROa2vQ61np2or63kLxckBz+skEaowdMB5gcAlQ4uFcAO0GwK8
faPABluLJwLgMpY=
-----END CERTIFICATE-----';

    private string $wrongCertificate = '-----BEGIN CERTIFICATE-----
MIIDBzCCAe+gAwIBAgIUSTkSsV7M2ypdR5Y3pKcypOEdfCUwDQYJKoZIhvcNAQEL
BQAwEzERMA8GA1UEAwwIVGVzdENlcnQwHhcNMjYwNDIyMDk0MTU3WhcNMjcwNDIy
MDk0MTU3WjATMREwDwYDVQQDDAhUZXN0Q2VydDCCASIwDQYJKoZIhvcNAQEBBQAD
ggEPADCCAQoCggEBAJoEmWPX3j0v256oKHcZsFttM+caRF+E2AH+q2lFLgv31G//
x/rgSCMwxT6OZoLHIRIgZIc3Au3AcNYnx52B66WU5egSl3snrLY8gHqSrXgBteQZ
T0yGffRCn531oSW6fESVIAhWWx9pZyuqjgP7qmOcg65iWw9/Wg71usJEN7+D0CZS
Dj39lvA3ZAlhoIeX2sVXUDBSidBFU5nQCUUrczEJqhlEtPg6WPn6X0moWNyh+Scd
gXi2PboEF1YJ9xmrQ69RVr4ShGB4JqQLA6yTUyTUe97Viii2JxyuiBNpZR/WxavX
GQLwLYbesXcyG+0XYOXT8rZcgfMpbHSdT6+340kCAwEAAaNTMFEwHQYDVR0OBBYE
FHHTmkcBFqzdGRKHwkySRW2AO+dNMB8GA1UdIwQYMBaAFHHTmkcBFqzdGRKHwkyS
RW2AO+dNMA8GA1UdEwEB/wQFMAMBAf8wDQYJKoZIhvcNAQELBQADggEBACBapRc7
BDvfAh1y7QltQhviDj5I538+MRNLSj73knOTYfgQBePJxJTdpTTqgPCQdhgtzza6
C6ch4EkzSOXv3Ys8rIGNwMKtXl82eexYbUijtYFMGbVEZumNl93jMMlclBKs4mkc
tKCiqSzAJVHDGyYq7z5zTNkd6urgUMvwcxXD63FeS02Rilqp2GeyE2kYoJ6TqJJ4
tEGZGuvFMUbGCVptHz1BOW0fKX6o/18zUP/3KiC+ou3iOHFYI1WzPMn97N+N2ip3
JB+DM3TjUw2pfxKeubM3pJSbGZ5i/8Zhkt84qFBlmwSuyFbwcp/KuNJ2bhDqgZ9s
DwFFvAJyARuSFWQ=
-----END CERTIFICATE-----';

    protected function setUp(): void
    {
        parent::setUp();
        $this->curlWrapperFactory = $this->createMock(CurlWrapperFactory::class);
        $this->tempCertificatePath = tempnam(sys_get_temp_dir(), 'cert');
        file_put_contents($this->tempCertificatePath, $this->dummyCertificate);
    }

    public function tearDown(): void
    {
        if (file_exists($this->tempCertificatePath)) {
            unlink($this->tempCertificatePath);
        }
        parent::tearDown();
    }

    private function getProperties(bool $legacy = false): ActesMinistereProperties
    {
        return new ActesMinistereProperties(
            'https://test-actes.gouv.fr/upload',
            ActesMinistereProperties::AUTHENTICATION_BASIC,
            'test-login',
            'test-password',
            '/path/to/client.cert',
            '/path/to/client.key',
            'key-pass',
            true,
            $this->tempCertificatePath,
            $legacy
        );
    }

    /**
     * @throws Exception
     */
    public function testSendSuccess(): void
    {
        $curlWrapperCheck = $this->createMock(CurlWrapper::class);
        $curlWrapperSend = $this->createMock(CurlWrapper::class);

        $this->curlWrapperFactory->expects($this->exactly(2))
            ->method('getNewInstance')
            ->willReturnOnConsecutiveCalls($curlWrapperCheck, $curlWrapperSend);

        $curlWrapperCheck->method('getServerCertificate')->willReturn($this->dummyCertificate);
        $curlWrapperSend->method('getHTTPCode')->willReturn(201);

        $sender = new ActesFileSender(
            $this->getProperties(),
            '/truststore/path',
            $this->curlWrapperFactory
        );

        $this->assertTrue($sender->send('/tmp/file.tar.gz'));
    }

    /**
     * @throws Exception
     */
    public function testSendLegacySuccess(): void
    {
        $curlWrapperCheck = $this->createMock(CurlWrapper::class);
        $curlWrapperSend = $this->createMock(CurlWrapper::class);

        $this->curlWrapperFactory->expects($this->exactly(2))
            ->method('getNewInstance')
            ->willReturnOnConsecutiveCalls($curlWrapperCheck, $curlWrapperSend);

        $curlWrapperCheck->method('getServerCertificate')->willReturn($this->dummyCertificate);
        $curlWrapperSend->method('getHTTPCode')->willReturn(200);

        $sender = new ActesFileSender(
            $this->getProperties(true),
            '/truststore/path',
            $this->curlWrapperFactory
        );

        $this->assertTrue($sender->send('/tmp/file.tar.gz'));
    }

    public function testSendCertificateMismatch(): void
    {
        $curlWrapperCheck = $this->createMock(CurlWrapper::class);

        $this->curlWrapperFactory->method('getNewInstance')->willReturn($curlWrapperCheck);
        $curlWrapperCheck->method('getServerCertificate')->willReturn($this->wrongCertificate);

        $sender = new ActesFileSender(
            $this->getProperties(),
            '/truststore/path',
            $this->curlWrapperFactory
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('ne correspond pas à celui attendu');

        $sender->send('/tmp/file.tar.gz');
    }

    public function testSendHttpError(): void
    {
        $curlWrapperCheck = $this->createMock(CurlWrapper::class);
        $curlWrapperSend = $this->createMock(CurlWrapper::class);

        $this->curlWrapperFactory->method('getNewInstance')
            ->willReturnOnConsecutiveCalls($curlWrapperCheck, $curlWrapperSend);

        $curlWrapperCheck->method('getServerCertificate')->willReturn($this->dummyCertificate);
        $curlWrapperSend->method('getHTTPCode')->willReturn(500);
        $curlWrapperSend->method('getLastError')->willReturn('Internal Server Error');

        $sender = new ActesFileSender(
            $this->getProperties(),
            '/truststore/path',
            $this->curlWrapperFactory
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Internal Server Error');

        $sender->send('/tmp/file.tar.gz');
    }
}
