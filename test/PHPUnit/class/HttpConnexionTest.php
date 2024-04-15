<?php

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

        $httpConnexion = new HttpsConnexion($environnement, $certificateHandler);
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

        $httpConnexion = new HttpsConnexion($environnement, $certificateHandler);
        $credentials = $httpConnexion->getCredentialsFromPost();

        $this->assertEquals("login", $credentials["login"]);
        $this->assertEquals("password", $credentials["password"]);
    }

    public function testAPIRequestWithHTTPLogin()
    {
        $get = ['api' => '1'];
        $serveur = [
            'PHP_AUTH_USER' => mb_convert_encoding("alice_é", 'ISO-8859-1', 'UTF-8'),
            'PHP_AUTH_PW' => "alice"
        ];

        $session = [];
        $environnement = new Environnement($get, [], [], $session, $serveur, true);
        $httpsConnexion = new HttpsConnexion(
            $environnement,
            $this->getObjectInstancier()->get(X509Certificate::class)
        );
        // Comme on a une authentification HTTP *et* la variable forceConversionFromIso,
        // s2low devrait considérer qu'on utilise l'API.

        $this->assertEquals(
            ["login" => "alice_é","password" => "alice"],
            $httpsConnexion->getCredentialsFromApache()
        );
    }
}
