<?php

use IntegrationTests\S2lowIntegrationTestCase;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\Authentification;
use S2lowLegacy\Class\Database;
use S2lowLegacy\Class\HttpsConnexion;
use S2lowLegacy\Class\PasswordHandler;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\PemCertificateFactory;
use S2lowLegacy\Lib\SessionWrapper;
use S2lowLegacy\Lib\X509Certificate;
use S2lowLegacy\Model\NounceSQL;
use S2lowLegacy\Model\UserSQL;

class AuthentificationTest extends S2lowIntegrationTestCase
{
    /**
     * @throws Exception
     */
    public function testAuthenticate()
    {
        $authentification = $this->getAuthentication();

        $this->expectExceptionMessage("Message : Aucune information de certificat trouvée");
        $this->assertEquals(13, $authentification->authenticate());
    }

    /**
     * @throws Exception
     */
    public function testAuthenticateCert()
    {
        $server = $this->setServerAdullactCertificate();
        $authentification = $this->getAuthentication($server);
        $this->assertEquals(13, $authentification->authenticate());
    }

    /**
     * @throws Exception
     */
    public function testManyCertWithLogin()
    {
        $this->logAs(50);
        $authentification = $this->getAuthentication($this->serverCertificatEnvVar);
        $this->expectExceptionMessage("Message : La connexion n'a pas pu être établie");
        $this->assertFalse($authentification->authenticate());
    }

    /**
     * @throws Exception
     */
    public function testManyCertLoginOk()
    {
        $file = file_get_contents($this->projectDir . '/test/PHPUnit/controller/fixtures/user1.pem');
        $pemCertificateFactory = new PemCertificateFactory();
        $certif = $pemCertificateFactory->getFromString(
            $file
        );

        $server = [
            'SSL_CLIENT_VERIFY' => 'SUCCESS',
            'SSL_CLIENT_S_DN' => 'subject_dn',
            'SSL_CLIENT_I_DN' => 'issuer_dn',
            'SSL_CLIENT_CERT' => $certif->getContent(),
        ];
        $authentification = $this->getAuthentication(
            server: $server,
            get: [
                'nounce' => 'nounce',
                'login' => 'login',
                'hash' => 'T5k4Cv8eWZMDNWo0h/a6DgDLTVw=',
            ]
        );
        $this->assertEquals(50, $authentification->authenticate());
    }

    /**login
     * @throws Exception
     */
    public function testManyCertLoginWithLogin()
    {
        $server = [
            'SSL_CLIENT_VERIFY' => 'SUCCESS',
            'SSL_CLIENT_S_DN' => 'test_subject',
            'SSL_CLIENT_I_DN' => 'test_issuer_2',
            'TESTING_CERTIFICATE_HASH' => 'T5k4Cv8eWZMDNWo0h/a6DgDLTVw=',
            'PHP_AUTH_USER' => 'login3',
            'PHP_AUTH_PW' => 'password',
        ];
        $authentification = $this->getAuthentication($server);

        $this->assertEquals(53, $authentification->authenticate());
    }

    /**
     * @throws Exception
     */
    public function testBadLogin()
    {
        $this->logAs(50);
        $server = $this->setServerAdullactCertificate();
        $server['PHP_AUTH_USER'] = "badlogin";
        $server['PHP_AUTH_PW'] = "password";
        $authentification = $this->getAuthentication($server);
        $this->expectExceptionMessage("Message : Le certificat n'est pas valide");
        $this->assertFalse($authentification->authenticate());
    }

    /**
     * @throws Exception
     */
    public function testNotLoginWithForwardCertificate()
    {
        $server = [];
        $server['SSL_CLIENT_VERIFY'] = 'SUCCESS';
        $server['SSL_CLIENT_S_DN'] = 'adullact_identificationa';
        $server['SSL_CLIENT_I_DN'] = 'adullact_identificationa';
        $server['TESTING_CERTIFICATE_HASH'] = 'hash_adullact_identificationa';

        $authentification = $this->getAuthentication(
            server: $server,
        );

        $this->expectExceptionMessage("Message : Le certificat n'est pas valide");
        $this->assertEquals(false, $authentification->authenticate());
    }

    /**
     * @throws Exception
     */
    public function testLoginWithForwardCertificate()
    {
        $this->logAs(13);
        $authentification = $this->getAuthentication(
            server: $this->serverCertificatEnvVar,
        );
        $this->assertEquals(13, $authentification->authenticate());
    }

    /**
     * @throws Exception
     */
    public function testAuthenticateWithCert()
    {
        $server = $this->setServerAdullactCertificate();
        $server['SSL_CLIENT_VERIFY'] = 'SUCCESS';
        $server['SSL_CLIENT_S_DN'] = 'adullact_identification';
        $server['SSL_CLIENT_I_DN'] = 'adullact_identification';
        $server['SSL_CLIENT_CERT'] = file_get_contents(__DIR__ . "/../controller/fixtures/test-certificat-not-in-db.pem");

        $authentification = $this->getAuthentication(
            server: $server,
        );
        $this->expectExceptionMessage("Message : Le certificat n'est pas valide");
        $this->assertEquals(4, $authentification->authenticate());
    }

    /**
     * @throws Exception
     */
    public function testAuthenticateWithBadCert()
    {
        $server = $this->setServerAdullactCertificate();
        $server['SSL_CLIENT_VERIFY'] = 'SUCCESS';
        $server['SSL_CLIENT_S_DN'] = 'adullact_identification';
        $server['SSL_CLIENT_I_DN'] = 'adullact_identification';
        $server['SSL_CLIENT_CERT'] = 'foo';

        $authentification = $this->getAuthentication(
            server: $server,
        );
        $this->expectExceptionMessage("Impossible de lire le certificat");
        $this->assertEquals(104, $authentification->authenticate());
    }

    /**
     * @throws Exception
     */
    public function testAuthentificationFailed()
    {
        $server = [];
        $server['SSL_CLIENT_VERIFY'] = 'FAILED';

        $authentification = $this->getAuthentication(
            server: $server,
            idLogin: 2
        );
        $this->expectExceptionMessage("Message : La connexion n'a pas pu être établie");
        $this->assertEquals(102, $authentification->authenticate());
    }

    /**
     * @throws Exception
     */
    public function testAuthentificationFailed2()
    {
        $server = [];
        $server['SSL_CLIENT_VERIFY'] = '';

        $authentification = $this->getAuthentication(
            server: $server,
            idLogin: 102
        );
        $this->expectExceptionMessage("Message : La connexion n'a pas pu être établie");
        $this->assertEquals(102, $authentification->authenticate());
    }

    /**
     * @throws Exception
     */
    public function testAuthenticateUser()
    {
        $this->setUserWithRole(UserRole::Utilisateur);
        $authentification = $this->getAuthentication($this->serverCertificatEnvVar);
        $this->assertEquals(13, $authentification->authenticate());
    }

    /**
     * @throws Exception
     * @dataProvider convertedLogins
     */
    public function testAuthenticationWithNounce(bool $convertLoginFromIso, string $login, string $encoding): void
    {
        $certHandler = $this->getMockBuilder(X509Certificate::class)->disableOriginalConstructor()->getMock();

        $certHandler->expects($this->atLeast(1))->method("getInfo")->willReturn([
            'expiration_date' => "01/01/2020",
            'issuer_name' => "test_issuer",
            'subject_name' => "test_subject",
            'certificate_hash' => "T5k4Cv8eWZMDNWo0h/a6DgDLTVw="
        ]);

        $nounceSQL = self::getContainer()->get(NounceSQL::class);
        $nounce = $nounceSQL->create($login, "alice", 2);
        $finalLogin = mb_convert_encoding($login, $encoding);
        $get = [
            'nounce' => $nounce,
            'login' => $finalLogin,
            'hash' => hash("sha256", "alice:$nounce")
        ];

        $server = [
            'SSL_CLIENT_VERIFY' => "SUCCESS",
            'SSL_CLIENT_S_DN' => "adullact",
            'SSL_CLIENT_I_DN' => "adullact",
            'SSL_CLIENT_CERT' => "certificat"
        ];

        $authentification = $this->getAuthentication(
            server: $server,
            get: $get,
            certHandler: $certHandler,
            convertLoginFromIso: $convertLoginFromIso
        );

        $this->assertEquals(51, $authentification->authenticate());
    }

    public function convertedLogins(): Generator
    {
        yield [false, 'login2', 'UTF-8'];
        yield [true, 'login2','ISO-8859-1'];
    }

    /**
     * @throws Exception
     */
    public function testAuthenticationWithNounceFailed()
    {
        $file = file_get_contents($this->projectDir . '/test/PHPUnit/controller/fixtures/user1.pem');
        $pemCertificateFactory = new PemCertificateFactory();
        $certif = $pemCertificateFactory->getFromString(
            $file
        );

        $server = [
            'SSL_CLIENT_VERIFY' => 'SUCCESS',
            'SSL_CLIENT_S_DN' => 'subject_dn',
            'SSL_CLIENT_I_DN' => 'issuer_dn',
            'SSL_CLIENT_CERT' => $certif->getContent(),
        ];
        $authentification = $this->getAuthentication(
            server: $server,
            get: [
                'nounce' => 'nounce',
                'login' => 'login',
                'hash' => 'BAD_HASH',
            ]
        );

        $this->expectExceptionMessage("La connexion n'a pas pu être établie");
        $authentification->authenticate();
    }

    public function testGetAllConnexionInfo()
    {
        $server = [
            'SSL_CLIENT_VERIFY' => "SUCCESS",
            'SSL_CLIENT_S_DN' => "adullact_identification",
            'SSL_CLIENT_I_DN' => "adullact_identification",
            'SSL_CLIENT_CERT' => file_get_contents(__DIR__ . "/fixtures/clean_pem.pem"),
        ];

        $authentification = $this->getAuthentication(
            server: $server,
            idLogin: 102
        );

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
