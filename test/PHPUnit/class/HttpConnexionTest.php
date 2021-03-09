<?php

class HttpConnexionTest extends S2lowTestCase{
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

        $httpConnexion = new HttpsConnexion($environnement,$certificateHandler);
        $this->assertFalse($httpConnexion->getCertificateInfo());

    }

    public function testGetCredentialsFromGet(){

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

        $httpConnexion = new HttpsConnexion($environnement,$certificateHandler);
        $credentials = $httpConnexion->getCredentialsFromPost();

        $this->assertEquals("login",$credentials["login"]);
        $this->assertEquals("password",$credentials["password"]);
    }
}