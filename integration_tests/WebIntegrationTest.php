<?php

declare(strict_types=1);

namespace IntegrationTests;

use Exception;
use S2low\Enum\ModulePermission;
use S2low\Enum\UserRole;
use S2lowLegacy\Lib\PemCertificateFactory;

/**
 *
 */
class WebIntegrationTest extends S2lowIntegrationTestCase
{
    /**
     * @throws \Exception
     */
    public function testCertificate(): void
    {
        $client = $this->client;
        $this->setUserWithRole(UserRole::SuperAdministrateur);

        $_GET = ['name' => 'ac-libriciel-personnel-g2-split1.pem'];// 2/ Le client symfony ne modifie pas la variable _SERVER
        $crawler = $client->request('GET', 'admin/utilities/certificate.php');
        static::assertMatchesRegularExpression(     //Un certificat est bien renvoyé
            '#-----BEGIN CERTIFICATE-----#',
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
        $_GET = [];
    }

    /**
     * @throws \Exception
     */
    public function testAddSirenController(): void
    {
        $client = $this->client;
        $this->setUserWithRole(UserRole::SuperAdministrateur);

        $_POST = [ 'id' => '1','siren' => '212901136'];

        $client->request('POST', '/admin/groups/add-siren-controler.php');
        static::assertMatchesRegularExpression(
            '#Le siren a été ajouté#',
            $_SESSION['error']
        );
        $_POST = [];
        static::assertResponseIsSuccessful();
        self::assertSame($_SERVER['SSL_CLIENT_VERIFY'], 'SUCCESS');
    }

    /**
     * @throws Exception
     */
    public function testListGroups(): void
    {
        $client = $this->client;
        $this->setUserWithRole(UserRole::SuperAdministrateur);

        $crawler = $client->request('POST', '/admin/groups/list_groups.php');
        static::assertMatchesRegularExpression(     //Le groupe de test est bien présent dans la page
            '#Groupe de test#',
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws Exception
     */
    public function testListCertificates(): void
    {
        $client = $this->client;
        $this->setUserWithRole(UserRole::SuperAdministrateur);

        $crawler = $client->request('GET', 'admin/utilities/certificate_list.php');
        static::assertMatchesRegularExpression(     //L'AC personnel ADULLACT G2 est bien présent'
            '#ac-libriciel-personnel-g2(.*).pem#',
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws Exception
     */
    public function testInfoConnexion(): void
    {
        $client = $this->client;
        $this->setUserWithRole(UserRole::SuperAdministrateur);

        $crawler = $client->request('GET', 'api/info-connexion.php');
        static::assertMatchesRegularExpression(     //On a bien le mail de l'user
            '#eric\+user@with-certif.com#',
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws Exception
     */
    public function testConnexion()
    {
        $client = $this->client;
        $this->setUserWithRole(UserRole::SuperAdministrateur);

        $crawler = $client->request('GET', 'api/test-connexion.php');
        static::assertMatchesRegularExpression(     //On a bien le mail de l'user
            '#OK#',
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws Exception
     */
    public function testRGS(): void
    {
        $this->logAs(2);
        $crawler = $this->client->request('GET', 'api/test-rgs.php');
        static::assertMatchesRegularExpression(     //Le certificat n'est pas RGS => KO
            '#KO#',
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws \Exception
     */
    public function testAncienSystemeNotif(): void
    {
        $client = $this->client;
        $this->setUserWithRole(UserRole::SuperAdministrateur);

        $_SERVER['QUERY_STRING'] = '';  // Autrement, ça ne fonctionne pas ...
        $crawler = $client->request('GET', 'admin/ancien_systeme_notif.php');
        static::assertMatchesRegularExpression(
            '#Bourg-en-Bresse#',        //On trouve bien la coll de test
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws \Exception
     */
    public function testAdminIndex(): void
    {
        $client = $this->client;
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $_SERVER['QUERY_STRING'] = '';  // Autrement, ça ne fonctionne pas ...
        $crawler = $client->request('GET', 'admin/index.php');
        static::assertMatchesRegularExpression(
            '#Console d\'administration#',
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws Exception
     */
    public function testPasDeSAE(): void
    {
        $client = $this->client;
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $_SERVER['QUERY_STRING'] = '';  // Autrement, ça ne fonctionne pas ...
        $crawler = $client->request('GET', 'admin/pas-de-sae.php');
        static::assertMatchesRegularExpression(
            '#Sur cette page, on ne présente que les collectivités qui n\'ont pas de SAE #',
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws \Exception
     */
    public function testStats(): void
    {
        $client = $this->client;
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $_SERVER['QUERY_STRING'] = '';  // Autrement, ça ne fonctionne pas ...
        $crawler = $client->request('GET', 'admin/stats.php');
        static::assertMatchesRegularExpression(
            '#Nombre de transactions#',
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }
}
