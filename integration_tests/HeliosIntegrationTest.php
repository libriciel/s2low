<?php

declare(strict_types=1);

namespace IntegrationTests;

use Exception;
use S2lowLegacy\Lib\ObjectInstancierFactory;
use S2lowLegacy\Lib\SQLQuery;

class HeliosIntegrationTest extends S2lowIntegrationTestCase
{
    use \HeliosUtilitiesTestTrait;

    /**
     * @throws Exception
     */
    public function testHeliosDeleteResponse(): void
    {
        $client = $this->setUpUser();
        $_SERVER['QUERY_STRING'] = '';  // Autrement, ça ne fonctionne pas ...
        $client->request('GET', 'modules/helios/admin/delete-response.php');
        static::assertMatchesRegularExpression(
            '#Impossible de lire le fichier.#',     //TODO : créer un test plus pertinent
            $_SESSION['error']
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws \Exception
     */
    public function testHeliosDownloadResponse(): void
    {
        $client = $this->setUpUser();
        $_SERVER['QUERY_STRING'] = '';  // Autrement, ça ne fonctionne pas ...
        $client->request('GET', 'modules/helios/admin/download-response.php');
        static::assertMatchesRegularExpression(
            '#Impossible de lire le fichier.#',     //TODO : créer un test plus pertinent
            $_SESSION['error']
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws \Exception
     */
    public function testHeliosAnalyseResponse(): void
    {
        $client = $this->setUpUser();
        $_SERVER['QUERY_STRING'] = '';  // Autrement, ça ne fonctionne pas ...
        $client->request('GET', 'modules/helios/admin/analyse-response.php');
        static::assertMatchesRegularExpression(
            '#Impossible de lire le fichier.#',     //TODO : créer un test plus pertinent
            $_SESSION['error']
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws \Exception
     */
    public function testHeliosResponseError(): void
    {
        $client = $this->setUpUser();
        $_SERVER['QUERY_STRING'] = '';  // Autrement, ça ne fonctionne pas ...
        $crawler = $client->request('GET', 'modules/helios/admin/responses-helios-error.php');
        static::assertMatchesRegularExpression(
            '#Liste des fichiers trouvés sur la plateforme Hélios mais dont l\'analyse a échoué.#',
            //TODO : créer un test plus pertinent
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws \Exception
     */
    public function testHeliosTransmisNonAcquitte(): void
    {
        $client = $this->setUpUser();
        $_SERVER['QUERY_STRING'] = '';  // Autrement, ça ne fonctionne pas ...
        $crawler = $client->request('GET', 'modules/helios/admin/transmis-non-acquitte.php');
        static::assertMatchesRegularExpression(
            '#Liste des transactions restées à l\'état transmis#',     //TODO : créer un test plus pertinent
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws \Exception
     */
    public function testHeliosBatchSign(): void
    {
        $client = $this->setUpUser();
        $_SERVER['QUERY_STRING'] = '';  // Autrement, ça ne fonctionne pas ...
        $crawler = $client->request('GET', 'modules/helios/helios_batch_sign.php');
        static::assertMatchesRegularExpression(
            '#Message : Vous devez sélectionner au moins une transaction à signer.#',
            //TODO : créer un test plus pertinent
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws \Exception
     */
    public function testHeliosStatsTransaction(): void
    {
        $client = $this->setUpUser();
        $_SERVER['QUERY_STRING'] = '';  // Autrement, ça ne fonctionne pas ...
        $crawler = $client->request('GET', 'modules/helios/helios_stats_transaction.php');
        static::assertMatchesRegularExpression(
            '#Tedetis : module helios statistique#',     //TODO : créer un test plus pertinent
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws \Exception
     */
    public function testHeliosTransacArchiver(): void
    {
        $client = $this->setUpUser();
        $_SERVER['QUERY_STRING'] = '';  // Autrement, ça ne fonctionne pas ...
        $client->request('GET', 'modules/helios/helios_transac_archiver.php');
        static::assertMatchesRegularExpression(
            '#Erreur: La transaction 0 n\'existe pas#',     //TODO : créer un test plus pertinent
            $_SESSION['error']
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws \Exception
     */
    public function testHeliosTransacClose(): void
    {
        $client = $this->setUpUser();
        $_SERVER['QUERY_STRING'] = '';  // Autrement, ça ne fonctionne pas ...
        $crawler = $client->request('GET', 'modules/helios/helios_transac_close.php');
        static::assertMatchesRegularExpression(
            '#foreach\(\) argument must be of type array|object, null given#',     //TODO : créer un test plus pertinent
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws \Exception
     */
    public function testHeliosTransacDelete(): void
    {
        $client = $this->setUpUser();
        $_SERVER['QUERY_STRING'] = '';  // Autrement, ça ne fonctionne pas ...
        $crawler = $client->request('GET', 'modules/helios/helios_transac_delete.php');
        static::assertMatchesRegularExpression(
            '#Invalid text representation: 7 ERREUR:  syntaxe en entrée invalide#',
            //TODO : créer un test plus pertinent
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws \Exception
     */
    public function testHeliosTransacRollBack(): void
    {
        $client = $this->setUpUser();

        $transaction_id = $this->createTransaction(1,);

        $_SERVER['QUERY_STRING'] = '';  // Autrement, ça ne fonctionne pas ...
        $_POST['id'] = $transaction_id;
        $client->request('GET', 'modules/helios/helios_transac_rollback.php');
        static::assertMatchesRegularExpression(
            "#La transaction $transaction_id est de nouveau à l\'état posté.#",
            $_SESSION['error']
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws \Exception
     */
    public function testHeliosTransacSetError(): void
    {
        $client = $this->setUpUser();
        $_SERVER['QUERY_STRING'] = '';  // Autrement, ça ne fonctionne pas ...
        $client->request('GET', 'modules/helios/helios_transac_set_error.php');
        static::assertMatchesRegularExpression(
            '#La transaction 0 a été passée en erreur.#',     //TODO : créer un test plus pertinent
            $_SESSION['error']
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws \Exception
     */
    public function testHeliosTransacSign(): void
    {
        $client = $this->setUpUser();
        $_SERVER['QUERY_STRING'] = '';  // Autrement, ça ne fonctionne pas ...
        $client->request('GET', 'modules/helios/helios_transac_sign.php');
        static::assertMatchesRegularExpression(
            '#La signature a été enregistrée#',     //TODO : créer un test plus pertinent
            $_SESSION['error']
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws \Exception
     */
    public function testHeliosTransacValidatePesAller(): void
    {
        $client = $this->setUpUser();
        $_SERVER['QUERY_STRING'] = '';  // Autrement, ça ne fonctionne pas ...
        $crawler = $client->request('GET', 'modules/helios/helios_transac_validate_pes_aller.php');
        static::assertMatchesRegularExpression(
            '#Trying to access array offset on value of type bool#',     //TODO : créer un test plus pertinent
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws \Exception
     */
    public function testHeliosIndex(): void
    {
        $client = $this->setUpUser();
        $_SERVER['QUERY_STRING'] = '';  // Autrement, ça ne fonctionne pas ...
        $crawler = $client->request('GET', 'modules/helios/index.php');
        static::assertMatchesRegularExpression(
            '#Liste des fichiers postés#',     //TODO : créer un test plus pertinent
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }
}
