<?php

declare(strict_types=1);

namespace IntegrationTests;

use Exception;
use S2low\Enum\ModulePermission;
use S2low\Enum\UserRole;

/**
 *
 */
class WebIntegrationTest extends S2lowIntegrationTestCase
{
    protected function tearDown(): void
    {

        parent::tearDown();
        self::ensureKernelShutdown();
    }
    /**
     * @throws \Exception
     */
    public function testCertificate(): void
    {
        $client = $this->getAuthenticatedClientWithSAdminUser();

        $_GET = ['name' => 'ac-libriciel-personnel-g2.pem'];// 2/ Le client symfony ne modifie pas la variable _SERVER
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
        $client = $this->getAuthenticatedClientWithSAdminUser();

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
        $client = $this->getAuthenticatedClientWithSAdminUser();

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
        $client = $this->getAuthenticatedClientWithSAdminUser();

        $crawler = $client->request('GET', 'admin/utilities/certificate_list.php');
        static::assertMatchesRegularExpression(     //L'AC personnel ADULLACT G2 est bien présent'
            '#ac-libriciel-personnel-g2.pem#',
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws Exception
     */
    public function testInfoConnexion(): void
    {
        $client = $this->getAuthenticatedClientWithSAdminUser();

        $crawler = $client->request('GET', 'api/info-connexion.php');
        static::assertMatchesRegularExpression(     //On a bien le mail de l'user
            '#eric@sigmalis.com#',
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws Exception
     */
    public function testConnexion()
    {
        $client = $this->getAuthenticatedClientWithSAdminUser();

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
        $certificatRgsDeuxEtoiles = $this->pemCertificateFactory->getFromString(
            file_get_contents(__DIR__ . '/../test/api/Eric_Pommateau_RGS_2_etoiles.pem')
        );

        $this->createUser(
            UserRole::SuperAdministrateur,
            $certificatRgsDeuxEtoiles->getContent(),
            $certificatRgsDeuxEtoiles->getHash(),
            ModulePermission::Modification
        );

        $client = $this->createClientWithCertificat(
            $certificatRgsDeuxEtoiles->getContent(),
            $certificatRgsDeuxEtoiles->getContentStrippedFromBegin()
        );

        $crawler = $client->request('GET', 'api/test-rgs.php');
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
        $client = $this->getAuthenticatedClientWithSAdminUser();

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
        $client = $this->getAuthenticatedClientWithSAdminUser();
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
        $client = $this->getAuthenticatedClientWithSAdminUser();
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
        $client = $this->getAuthenticatedClientWithSAdminUser();
        $_SERVER['QUERY_STRING'] = '';  // Autrement, ça ne fonctionne pas ...
        $crawler = $client->request('GET', 'admin/stats.php');
        static::assertMatchesRegularExpression(
            '#Nombre de transactions#',
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws Exception
     */
    public function testEditAnnuaire(): void
    {
        $client = $this->getAuthenticatedClientWithSAdminUser();
        $_SERVER['QUERY_STRING'] = '';  // Autrement, ça ne fonctionne pas ...
        $crawler = $client->request('GET', 'modules/mail/edit-annuaire.php');
        static::assertMatchesRegularExpression(
            '#exit\(\) called#',     //TODO : créer un test plus pertinent (Il faut un admin de coll )
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }
}
