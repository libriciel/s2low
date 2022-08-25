<?php

use S2lowLegacy\Class\Authentification;
use S2lowLegacy\Class\HttpsConnexion;
use S2lowLegacy\Class\PasswordHandler;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\X509Certificate;
use S2lowLegacy\Model\NounceSQL;
use S2lowLegacy\Model\UserSQL;

class AuthentificationTest extends S2lowTestCase
{
    /**
     * @param $expected
     * @throws Exception
     */
    private function authenticateWith($expected)
    {
        $authentification = $this->getObjectInstancier()->get(Authentification::class);
        $this->assertEquals($expected, $authentification->authenticate());
    }

    /**
     * @throws Exception
     */
    public function testAuthenticate()
    {
        $this->setExpectedException("Exception", "Message : Aucune information de certificat trouvée");
        $this->authenticateWith(false);
    }

    /**
     * @throws Exception
     */
    public function testAuthenticateCert()
    {
        $this->setSuperAdminAuthentication();
        $this->authenticateWith(1);
    }

    private function setServerAdullactCertificate()
    {
        $this->setServerInfo([
            'SSL_CLIENT_VERIFY' => "SUCCESS",
            'SSL_CLIENT_S_DN' => "adullact",
            'SSL_CLIENT_I_DN' => "adullact",
            'TESTING_CERTIFICATE_HASH' => "hash_adullact",
        ]);
    }

    /**
     * @throws Exception
     */
    public function testManyCertWithLogin()
    {
        $this->setServerAdullactCertificate();
        $this->setExpectedException("Exception", "Message : La connexion n'a pas pu être établie");
        $this->authenticateWith(false);
    }

    /**
     * @throws Exception
     */
    public function testManyCertLoginOk()
    {
        $this->setServerAdullactCertificate();
        $this->getObjectInstancier()->get(Environnement::class)->session()->set('id_login', 2);
        $this->authenticateWith(2);
    }

    /**
     * @throws Exception
     */
    public function testManyCertLoginWithLogin()
    {
        $this->setServerAdullactCertificate();
        $this->setServerInfo([
            'PHP_AUTH_USER' => "alice",
            'PHP_AUTH_PW' => "alice"
        ]);
        $this->authenticateWith(2);
    }

    /**
     * @throws Exception
     */
    public function testBadLogin()
    {
        $this->setServerAdullactCertificate();
        $this->setServerInfo([
            'PHP_AUTH_USER' => "alice",
            'PHP_AUTH_PW' => "password"
        ]);
        $this->setExpectedException("Exception", "Message : Le certificat n'est pas valide");
        $this->authenticateWith(false);
    }

    /**
     * @throws Exception
     */
    public function testNotLoginWithForwardCertificate()
    {
        $this->setServerInfo([
            'SSL_CLIENT_VERIFY' => "SUCCESS",
            'SSL_CLIENT_S_DN' => "adullact_identification",
            'SSL_CLIENT_I_DN' => "adullact_identification",
            'TESTING_CERTIFICATE_HASH' => "hash_adullact_identification"
        ]);

        $this->setExpectedException("Exception", "Message : Le certificat n'est pas valide");
        $this->authenticateWith(false);
    }

    /**
     * @throws Exception
     */
    public function testLoginWithForwardCertificate()
    {
        $this->setServerInfo([
            'SSL_CLIENT_VERIFY' => "SUCCESS",
            'SSL_CLIENT_S_DN' => "adullact_identification",
            'SSL_CLIENT_I_DN' => "adullact_identification",
            'TESTING_CERTIFICATE_HASH' => "hash_adullact_identification",
            'HTTP_ORG_S2LOW_FORWARD_X509_IDENTIFICATION' => 'MIIFeTCCA2ECAQgwDQYJKoZIhvcNAQEFBQAwgYoxCzAJBgNVBAYTAkZSMQ8wDQYDVQQIDAZGcmFuY2UxDTALBgNVBAcMBEx5b24xETAPBgNVBAoMCFNpZ21hbGlzMSYwJAYDVQQDDB1TaWdtYWxpcyBDZXJ0aWZpY2F0ZSBBdXRvcml0eTEgMB4GCSqGSIb3DQEJARYRZXJpY0BzaWdtYWxpcy5jb20wHhcNMTUwODE5MDgzMzU5WhcNMjUwODE2MDgzMzU5WjB6MQswCQYDVQQGEwJGUjEPMA0GA1UECAwGRnJhbmNlMQ0wCwYDVQQHDARMeW9uMREwDwYDVQQKDAhTaWdtYWxpczERMA8GA1UECwwIc2lnbWFsaXMxJTAjBgNVBAMMHEVyaWNfUG9tbWF0ZWF1X1JHU18yX2V0b2lsZXMwggIiMA0GCSqGSIb3DQEBAQUAA4ICDwAwggIKAoICAQDYdUMag6AQO7uepqYJm1Uyi/U/zpgIm+8LpWIZJsFQj++dXcDHa+fV8TGun8H8tqVMfDwNd+VREgDiatU8v/PZDJw2ZjTETGC1qeN2eM3ZrOXvur8y8m5j1KPtT9y2M8k204NW0mf/weoYVSulEQbsyoQJfIMu7ALi/XvFXkGjvpG/BRr8MfSh7GtUtaGJhpGVTwv0gHXXGorixgGPhDNVE8Wr2mn/icfb/hpfQamO62W/fP4p1thGo5CMhqjyl6PLseU76nD9lUzWZtLSE1/1885zWsHGqD63Vhc/8Dr89GqCqKdBM4egwlQvT8diTZpeYSRCxHAybiPSAu5WUd0UMARabiQGbXrR+Rqs+C2W5WkUrwU8bwvpZlPF/BiGWAMayqA3xos7uqHFQjlNtg7wRir4dYxNH/whbl0Gu5dOMbFcFU/mqWpvEPIpIG5Ym7shoUYUH2x8T5TGvIn3a5BUCGmH9n/DH8ybNqD63hlsdQ2Bnt4n/nZfS1a326j3EA4eALeQ0vbLxWqoy7hASzkfFO7YDEi2U4rucfAXJWDq4O3HzPl+aQseken7DBLGfNobl77JKYIadJqbvDXNueTm6+l7r/okg76xCUIQr5Sp+x4YpVolt/FG6KY0LNfTnByniWwQoOXh4Az4qGuiGgV+l9gOuZRTr/KrZ6tXZ9T3iQIDAQABMA0GCSqGSIb3DQEBBQUAA4ICAQCTL+p4cZLzQJ2boA43xX/YdJRTPNKaka0BywJ5HIkFEm5YNVgxrqfoQZC+DxCqAGJwQm7+HkDZpWr2RkmloVHFrancpkcWU1Vca5jN9oPg6rMlQLiLz4hnO8XAjcYBR4neAIDd8DP5kwH/Kj36vPqu0ki5osd70G4ZpsBoW4BXVEPhwLKTBzQvZREsC+654k/JAbAYj/FQba9jaudfbO5xVLbNlhYKv+Iz+pUGJIP+Sr5wykDuWuyLwnnyvg0WQ0CeUWgG0x4D7Ef828i92ZC/CumpjaRYKMPYEzitndNW36K1CldGfCuNUqAQmqXIPZqyaqQ0H7N8BnISwdBCojZEljwfNOxHLrT1MQDDacl5gLEEYKj8JW86UiMAo3ONIi1HYT+eo4Sx/BzH9GhUwT8IUuiHnl721SzNuIRzB++VurtvQVc5cDumko6Qy+VgRnxPbzk32ortsBYUAFZUpoGA1f1BW4wgpYN8mfzmXBL88ugP7bWYQLV6wxoBW44IbLnIPJoWP8c13YWC2pC8DIOXzXONyPThsQ7QoSMwU27XzH1zb+NiD8sHNPgHacK6gSg/ZBj53IMGtElUAw3RRgXbuYnKeprALP5oks/IqINKST3K68njxMHj/v/hduEkw0dJxD5J/ga9beBhZ2Soe7XqBuUvYNN6Z4fNWGHPgI7R6w=='
        ]);
        $this->authenticateWith(4);
    }


    /**
     * @throws Exception
     */
    public function testAuthenticateWithCert()
    {
        $this->setServerInfo([
            'SSL_CLIENT_VERIFY' => "SUCCESS",
            'SSL_CLIENT_S_DN' => "adullact_identification",
            'SSL_CLIENT_I_DN' => "adullact_identification",
            'SSL_CLIENT_CERT' => file_get_contents(__DIR__ . "/../controller/fixtures/user1.pem"),
        ]);
        $this->setExpectedException("Exception", "Message : Le certificat n'est pas valide");
        $this->authenticateWith(4);
    }

    /**
     * @throws Exception
     */
    public function testAuthenticateWithBadCert()
    {
        $this->setServerInfo([
            'SSL_CLIENT_VERIFY' => "SUCCESS",
            'SSL_CLIENT_S_DN' => "adullact_identification",
            'SSL_CLIENT_I_DN' => "adullact_identification",
            'SSL_CLIENT_CERT' => 'foo',
        ]);
        $this->setExpectedException("Exception", "Impossible de lire le certificat");
        $this->authenticateWith(4);
    }

    /**
     * @throws Exception
     */
    public function testAuthentificationFailed()
    {
        $this->setServerInfo([
            'SSL_CLIENT_VERIFY' => "FAILED",
        ]);
        $this->getObjectInstancier()->get(Environnement::class)->session()->set('id_login', 2);
        $this->setExpectedException("Exception", "Message : La connexion n'a pas pu être établie");
        $this->authenticateWith(2);
    }

    /**
     * @throws Exception
     */
    public function testAuthentificationFailed2()
    {
        $this->setServerInfo([
            'SSL_CLIENT_VERIFY' => "",
        ]);
        $this->getObjectInstancier()->get(Environnement::class)->session()->set('id_login', 2);
        $this->setExpectedException("Exception", "Message : La connexion n'a pas pu être établie");
        $this->authenticateWith(2);
    }

    /**
     * @throws Exception
     */
    public function testAuthenticateUser()
    {
        $this->setUserAuthentification();
        $this->authenticateWith(8);
    }

    /**
     * @throws Exception
     */
    public function testAuthenticationWithNounce()
    {
        $nounceSQL = $this->getObjectInstancier()->get(NounceSQL::class);
        $nounce = $nounceSQL->create("alice", "alice", 1);

        $this->setServerInfo([
            'SSL_CLIENT_VERIFY' => "SUCCESS",
            'SSL_CLIENT_S_DN' => "adullact",
            'SSL_CLIENT_I_DN' => "adullact",
            'SSL_CLIENT_CERT' => "certificat"
        ]);

        $this->getObjectInstancier()->get(Environnement::class)->get()->set('nounce', $nounce);
        $this->getObjectInstancier()->get(Environnement::class)->get()->set('login', 'alice');
        $this->getObjectInstancier()->get(Environnement::class)->get()->set('hash', hash("sha256", "alice:$nounce"));

        $certHandler = $this->getMockBuilder(X509Certificate::class)->disableOriginalConstructor()->getMock();

        $certHandler->expects($this->atLeast(1))->method("getInfo")->willReturn([
            'expiration_date' => "01/01/2020",
            'issuer_name' => "issuer_name",
            'subject_name' => "subject_name",
            'certificate_hash' => "hash_adullact"
        ]);

        $environment = $this->getObjectInstancier()->get(Environnement::class);

        $httpsConnexion = new HttpsConnexion($environment, $certHandler);

        $authentification = new Authentification(
            $environment,
            $this->getObjectInstancier()->get(UserSQL::class),
            $this->getObjectInstancier()->get(PasswordHandler::class),
            $httpsConnexion,
            $nounceSQL
        );

        $this->assertEquals(2, $authentification->authenticate());
    }

    /**
     * @throws Exception
     */
    public function testAuthenticationWithNounceFailed()
    {
        $nounceSQL = $this->getObjectInstancier()->get(NounceSQL::class);
        $nounce = $nounceSQL->create("alice", "alice", 1);

        $this->setServerAdullactCertificate();
        $this->getObjectInstancier()->get(Environnement::class)->get()->set('nounce', $nounce);
        $this->getObjectInstancier()->get(Environnement::class)->get()->set('login', 'alice');
        $this->getObjectInstancier()->get(Environnement::class)->get()->set('hash', hash("sha256", "alice:$nounce:toto"));

        $authentification = $this->getObjectInstancier()->get(Authentification::class);
        $this->setExpectedException("Exception", "La connexion n'a pas pu être établie");
        $authentification->authenticate();
    }

    public function testGetAllConnexionInfo()
    {
        $this->setServerInfo([
            'SSL_CLIENT_VERIFY' => "SUCCESS",
            'SSL_CLIENT_S_DN' => "adullact_identification",
            'SSL_CLIENT_I_DN' => "adullact_identification",
            'SSL_CLIENT_CERT' => file_get_contents(__DIR__ . "/fixtures/clean_pem.pem"),
        ]);

        $authentification = $this->getObjectInstancier()->get(Authentification::class);
        $info = $authentification->getAllConnexionInfo();

        $this->assertEquals(array(
            'ssl_client_verify' => 'SUCCESS',
            'subject_dn' => '/C=FR/ST=France/L=Lyon/O=Sigmalis/OU=sigmalis/CN=Eric_Pommateau_RGS_2_etoiles',
            'issuer_dn' => '/C=FR/ST=France/L=Lyon/O=Sigmalis/CN=Sigmalis Certificate Autority/emailAddress=eric@sigmalis.com',
            'ssl_client_cert' => '-----BEGIN CERTIFICATE-----
MIIFeTCCA2ECAQgwDQYJKoZIhvcNAQEFBQAwgYoxCzAJBgNVBAYTAkZSMQ8wDQYD
VQQIDAZGcmFuY2UxDTALBgNVBAcMBEx5b24xETAPBgNVBAoMCFNpZ21hbGlzMSYw
JAYDVQQDDB1TaWdtYWxpcyBDZXJ0aWZpY2F0ZSBBdXRvcml0eTEgMB4GCSqGSIb3
DQEJARYRZXJpY0BzaWdtYWxpcy5jb20wHhcNMTUwODE5MDgzMzU5WhcNMjUwODE2
MDgzMzU5WjB6MQswCQYDVQQGEwJGUjEPMA0GA1UECAwGRnJhbmNlMQ0wCwYDVQQH
DARMeW9uMREwDwYDVQQKDAhTaWdtYWxpczERMA8GA1UECwwIc2lnbWFsaXMxJTAj
BgNVBAMMHEVyaWNfUG9tbWF0ZWF1X1JHU18yX2V0b2lsZXMwggIiMA0GCSqGSIb3
DQEBAQUAA4ICDwAwggIKAoICAQDYdUMag6AQO7uepqYJm1Uyi/U/zpgIm+8LpWIZ
JsFQj++dXcDHa+fV8TGun8H8tqVMfDwNd+VREgDiatU8v/PZDJw2ZjTETGC1qeN2
eM3ZrOXvur8y8m5j1KPtT9y2M8k204NW0mf/weoYVSulEQbsyoQJfIMu7ALi/XvF
XkGjvpG/BRr8MfSh7GtUtaGJhpGVTwv0gHXXGorixgGPhDNVE8Wr2mn/icfb/hpf
QamO62W/fP4p1thGo5CMhqjyl6PLseU76nD9lUzWZtLSE1/1885zWsHGqD63Vhc/
8Dr89GqCqKdBM4egwlQvT8diTZpeYSRCxHAybiPSAu5WUd0UMARabiQGbXrR+Rqs
+C2W5WkUrwU8bwvpZlPF/BiGWAMayqA3xos7uqHFQjlNtg7wRir4dYxNH/whbl0G
u5dOMbFcFU/mqWpvEPIpIG5Ym7shoUYUH2x8T5TGvIn3a5BUCGmH9n/DH8ybNqD6
3hlsdQ2Bnt4n/nZfS1a326j3EA4eALeQ0vbLxWqoy7hASzkfFO7YDEi2U4rucfAX
JWDq4O3HzPl+aQseken7DBLGfNobl77JKYIadJqbvDXNueTm6+l7r/okg76xCUIQ
r5Sp+x4YpVolt/FG6KY0LNfTnByniWwQoOXh4Az4qGuiGgV+l9gOuZRTr/KrZ6tX
Z9T3iQIDAQABMA0GCSqGSIb3DQEBBQUAA4ICAQCTL+p4cZLzQJ2boA43xX/YdJRT
PNKaka0BywJ5HIkFEm5YNVgxrqfoQZC+DxCqAGJwQm7+HkDZpWr2RkmloVHFranc
pkcWU1Vca5jN9oPg6rMlQLiLz4hnO8XAjcYBR4neAIDd8DP5kwH/Kj36vPqu0ki5
osd70G4ZpsBoW4BXVEPhwLKTBzQvZREsC+654k/JAbAYj/FQba9jaudfbO5xVLbN
lhYKv+Iz+pUGJIP+Sr5wykDuWuyLwnnyvg0WQ0CeUWgG0x4D7Ef828i92ZC/Cump
jaRYKMPYEzitndNW36K1CldGfCuNUqAQmqXIPZqyaqQ0H7N8BnISwdBCojZEljwf
NOxHLrT1MQDDacl5gLEEYKj8JW86UiMAo3ONIi1HYT+eo4Sx/BzH9GhUwT8IUuiH
nl721SzNuIRzB++VurtvQVc5cDumko6Qy+VgRnxPbzk32ortsBYUAFZUpoGA1f1B
W4wgpYN8mfzmXBL88ugP7bWYQLV6wxoBW44IbLnIPJoWP8c13YWC2pC8DIOXzXON
yPThsQ7QoSMwU27XzH1zb+NiD8sHNPgHacK6gSg/ZBj53IMGtElUAw3RRgXbuYnK
eprALP5oks/IqINKST3K68njxMHj/v/hduEkw0dJxD5J/ga9beBhZ2Soe7XqBuUv
YNN6Z4fNWGHPgI7R6w==
-----END CERTIFICATE-----',
            'certificate_rgs_2_etoiles' => false,
            'login' => false,
            'password' => false,
            'certificate_hash' => 'ieQoLUcitdU9iZIJLPoIdp8TcUY=',
        ), $info);
    }

    public function testAuthentByForm()
    {
        $environnement = $this->getMockBuilder(Environnement::class)->disableOriginalConstructor()->getMock();
        $userSQL = $this->getMockBuilder(UserSQL::class)->disableOriginalConstructor()->getMock();
        $passwordHandler = $this->getMockBuilder(PasswordHandler::class)->disableOriginalConstructor()->getMock();
        $httpsConnexion = $this->getMockBuilder(HttpsConnexion::class)->disableOriginalConstructor()->getMock();

        $httpsConnexion->expects($this->once())->method('getCredentialsFromPost')->willReturn(['credentials']);
        $httpsConnexion->expects($this->never())->method('getCredentialsFromApache');

        $httpsConnexion->expects($this->once())->method('getCertificateInfo')->willReturn(['certificate_infos']);

        $authentification = new Authentification(
            $environnement,
            $userSQL,
            $passwordHandler,
            $httpsConnexion
        );

        $authentification->getAllConnexionInfo(Authentification::AUTHENTIFICATION_BY_FORM);
    }

    public function testAuthentByApache()
    {
        $environnement = $this->getMockBuilder(Environnement::class)->disableOriginalConstructor()->getMock();
        $userSQL = $this->getMockBuilder(UserSQL::class)->disableOriginalConstructor()->getMock();
        $passwordHandler = $this->getMockBuilder(PasswordHandler::class)->disableOriginalConstructor()->getMock();
        $httpsConnexion = $this->getMockBuilder(HttpsConnexion::class)->disableOriginalConstructor()->getMock();

        $httpsConnexion->expects($this->never())->method('getCredentialsFromPost');
        $httpsConnexion->expects($this->once())->method('getCredentialsFromApache');

        $httpsConnexion->expects($this->once())->method('getCertificateInfo')->willReturn(['certificate_infos']);

        $authentification = new Authentification(
            $environnement,
            $userSQL,
            $passwordHandler,
            $httpsConnexion
        );

        $authentification->getAllConnexionInfo(Authentification::AUTHENTIFICATION_BY_APACHE);
    }

    public function testAuthentByInexistingMethod()
    {
        $environnement = $this->getMockBuilder(Environnement::class)->disableOriginalConstructor()->getMock();
        $userSQL = $this->getMockBuilder(UserSQL::class)->disableOriginalConstructor()->getMock();
        $passwordHandler = $this->getMockBuilder(PasswordHandler::class)->disableOriginalConstructor()->getMock();
        $httpsConnexion = $this->getMockBuilder(HttpsConnexion::class)->disableOriginalConstructor()->getMock();

        $httpsConnexion->expects($this->never())->method('getCredentialsFromPost');
        $httpsConnexion->expects($this->never())->method('getCredentialsFromApache');

        $authentification = new Authentification(
            $environnement,
            $userSQL,
            $passwordHandler,
            $httpsConnexion
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Méthode d'authentification non reconnue");
        $this->assertFalse($authentification->getAllConnexionInfo(984645));
    }
}
