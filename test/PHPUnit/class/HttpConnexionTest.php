<?php

declare(strict_types=1);

namespace PHPUnit\class;

use PHPUnit\S2lowTestCase;
use S2lowLegacy\Class\HttpsConnexion;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\SessionWrapper;
use S2lowLegacy\Lib\X509Certificate;

class HttpConnexionTest extends S2lowTestCase
{
    public function testEmptyInfoSslClientCert()
    {
        $standardLocation = $this->getMockBuilder(SessionWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $standardLocation->method("get")->willReturn("value");

        $environnement = $this->getMockBuilder(Environnement::class)
            ->disableOriginalConstructor()
            ->getMock();

        $environnement->method('server')->willReturn($standardLocation);

        $certificateHandler = $this->getMockBuilder(X509Certificate::class)
            ->disableOriginalConstructor()
            ->getMock();

        $certificateHandler->expects($this->once())->method('getInfo')->willReturn(false);

        $httpConnexion = new HttpsConnexion($environnement, $certificateHandler, true);
        $this->assertFalse($httpConnexion->getCertificateInfo());
    }

    public function testGetCredentialsFromGet()
    {

        $standardLocation = $this->getMockBuilder(SessionWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $standardLocation->method("get")->will($this->returnArgument(0));

        $environnement = $this->getMockBuilder(Environnement::class)
            ->disableOriginalConstructor()
            ->getMock();

        $environnement->method('post')->willReturn($standardLocation);

        $certificateHandler = $this->getMockBuilder(X509Certificate::class)
            ->disableOriginalConstructor()
            ->getMock();

        $httpConnexion = new HttpsConnexion($environnement, $certificateHandler, true);
        $credentials = $httpConnexion->getCredentialsFromPost();

        $this->assertEquals("login", $credentials["login"]);
        $this->assertEquals("password", $credentials["password"]);
    }
}
