<?php

declare(strict_types=1);

namespace IntegrationTests;

use Exception;
use HeliosUtilitiesTestTrait;
use S2low\Enum\UserRole;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use SplFileInfo;

class HeliosIntegrationTest extends S2lowIntegrationTestCase
{
    use HeliosUtilitiesTestTrait;

    private HeliosTransactionsSQL $heliosTransactionsSQL;

    protected function setUp(): void
    {
        parent::setUp();
        $this->heliosTransactionsSQL = new HeliosTransactionsSQL($this->sqlQuery);
    }

    protected function tearDown(): void
    {
        $heliosResponsesErrorPath = self::getContainer()->getParameter('app.helios_responses_error_path');
        foreach (glob($heliosResponsesErrorPath . '/*') as $file) {
            unlink($file);
        }
        parent::tearDown();
    }

    /**
     * @throws Exception
     */
    public function testHeliosDeleteResponse(): void
    {
        $client = $this->client;
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $pesAller = $this->addPesAllerInErrorPath();

        $_GET['file'] = $pesAller->getFilename();

        $client->request('GET', 'modules/helios/admin/delete-response.php');
        static::assertMatchesRegularExpression(
            '#Le fichier ' . preg_quote($pesAller->getFilename()) . ' a été supprimé#',
            $_SESSION['error']
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
        static::assertFileDoesNotExist($pesAller->getPathname());
    }

    /**
     * @throws \Exception
     */
    public function testHeliosDownloadResponse(): void
    {
        $client = $this->client;
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $pesAller = $this->addPesAllerInErrorPath();

        $_GET['file'] = $pesAller->getFilename();
        $crawler = $client->request('GET', 'modules/helios/admin/download-response.php');
        static::assertMatchesRegularExpression(
            '#03f432a4f6d35110bf309fb525eb61f7#',           //nomfic du pes_aller de test
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws \Exception
     */
    public function testHeliosAnalyseResponse(): void
    {
        $client = $this->client;
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $pesAller = $this->addPesAllerInErrorPath();

        $_GET['file'] = $pesAller->getFilename();

        $client->request('GET', 'modules/helios/admin/analyse-response.php');
        static::assertMatchesRegularExpression(
            '#identificant NomFic 03f432a4f6d35110bf309fb525eb61f7#',
            $_SESSION['error']
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws Exception
     */
    public function testHeliosResponseError(): void
    {
        $client = $this->client;
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $pesAller = $this->addPesAllerInErrorPath();

        $crawler = $client->request('GET', 'modules/helios/admin/responses-helios-error.php');

        static::assertMatchesRegularExpression(
            '#Liste des fichiers trouvés sur la plateforme Hélios mais dont l\'analyse a échoué.#',
            $crawler->html()
        );
        static::assertMatchesRegularExpression(
            '#' . preg_quote($pesAller->getFilename()) . '#',
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws \Exception
     */
    public function testHeliosTransmisNonAcquitte(): void
    {
        $client = $this->client;
        $this->setUserWithRole(UserRole::SuperAdministrateur);

        $this->createTransaction(
            1,
            HeliosTransactionsSQL::TRANSMIS,
            '1970-05-28 16:13:51.945858+02'
        );

        $crawler = $client->request('GET', 'modules/helios/admin/transmis-non-acquitte.php');

        static::assertMatchesRegularExpression(
            '#Liste des transactions restées à l\'état transmis#',
            $crawler->html()
        );
        static::assertMatchesRegularExpression(
            '#toto\.txt#',                  //Le nom du fichier créé par HeliosUtilitiesTestTrait
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws \Exception
     */
    public function testHeliosBatchSign(): void
    {
        $client = $this->getAuthenticatedClientWithSAdminUser();

        $crawler = $client->request('GET', 'modules/helios/helios_batch_sign.php');

        static::assertMatchesRegularExpression(
            '#Message : Vous devez sélectionner au moins une transaction à signer.#',
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws \Exception
     */
    public function testHeliosStatsTransaction(): void
    {
        $client = $this->client;
        $this->setUserWithRole(UserRole::SuperAdministrateur);

        $crawler = $client->request('GET', 'modules/helios/helios_stats_transaction.php');

        static::assertMatchesRegularExpression(
            '#Tedetis : module helios statistique#',
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws \Exception
     */
    public function testHeliosTransacArchiver(): void
    {
        $client = $this->client;
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $transaction_id = $this->createTransaction(
            1,
            HeliosTransactionsSQL::INFORMATION_DISPONIBLE,
        );

        $_POST['id'] = $transaction_id;

        $client->request('GET', 'modules/helios/helios_transac_archiver.php');
        static::assertMatchesRegularExpression(
            '#Erreur: La collectivité n\'a pas de Pastell configuré#',
            $_SESSION['error']
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws \Exception
     */
    public function testHeliosTransacClose(): void
    {
        $client = $this->client;
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $transaction_id = $this->createTransaction(
            1,
            HeliosTransactionsSQL::INFORMATION_DISPONIBLE,
        );

        $_POST['liste_id'] = [$transaction_id];

        $client->request('GET', 'modules/helios/helios_transac_close.php');

        static::assertMatchesRegularExpression(
            '#La collectivité n\'a pas de Pastell configuré#',
            $_SESSION['error']
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws \Exception
     */
    public function testHeliosTransacDelete(): void
    {
        $client = $this->client;
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $transaction_id = $this->createTransaction(
            1,
            HeliosTransactionsSQL::INFORMATION_DISPONIBLE,
        );

        $_POST['id'] = $transaction_id;

        $crawler = $client->request('GET', 'modules/helios/helios_transac_delete.php');
        static::assertMatchesRegularExpression(
            '#Location: index.php#',
            $crawler->html()
        );
        static::assertMatchesRegularExpression(
            "#La transaction $transaction_id a été éradiquée ....#",
            $_SESSION['error']
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws \Exception
     */
    public function testHeliosTransacRollBack(): void
    {
        $client = $this->client;
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $transaction_id = $this->createTransaction();

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
        $client = $this->client;
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $transaction_id = $this->createTransaction();

        $_POST['id'] = $transaction_id;

        $client->request('GET', 'modules/helios/helios_transac_set_error.php');
        static::assertMatchesRegularExpression(
            "#La transaction $transaction_id a été passée en erreur.#",
            $_SESSION['error']
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws \Exception
     */
    public function testHeliosTransacSign(): void
    {
        $client = $this->client;
        $this->setUserWithRole(UserRole::SuperAdministrateur);

        $client->request('GET', 'modules/helios/helios_transac_sign.php');
        static::assertMatchesRegularExpression(
            '#La signature a été enregistrée#',
            $_SESSION['error']
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws \Exception
     */
    public function testHeliosTransacValidatePesAller(): void
    {
        $client = $this->client;
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $transaction_id = $this->createTransaction();

        $_GET['id'] = $transaction_id;
        $this->expectError();           //Le Pes Aller n'est pas set
        $client->request('GET', 'modules/helios/helios_transac_validate_pes_aller.php');
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws \Exception
     */
    public function testHeliosIndex(): void
    {
        $client = $this->client;
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $this->createTransaction();

        $crawler = $client->request('GET', 'modules/helios/index.php');
        static::assertMatchesRegularExpression(
            '#Liste des fichiers postés#',
            $crawler->html()
        );
        static::assertMatchesRegularExpression(
            '#toto\.txt#',                      //Le nom du fichier créé par HeliosUtilitiesTestTrait
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    public function testHeliosTransacSubmitPostsOwnTransaction(): void
    {
        $transactionId = $this->createTransaction(1, HeliosTransactionsSQL::ATTENTE_POSTEE);
        $this->setUserWithRole(UserRole::Utilisateur);
        $_POST['id'] = $transactionId;

        $this->client->request('GET', 'modules/helios/helios_transac_submit.php');

        static::assertSame(HeliosTransactionsSQL::POSTE, $this->getLastStatusId($transactionId));
    }

    public function testHeliosTransacSubmitRefusesTransactionOfAnotherAuthority(): void
    {
        $userOfAnotherAuthority = 51;
        $transactionId = $this->createTransaction(2, HeliosTransactionsSQL::ATTENTE_POSTEE);
        $this->sqlQuery->query('UPDATE helios_transactions SET user_id = ? WHERE id = ?', $userOfAnotherAuthority, $transactionId);
        $this->setUserWithRole(UserRole::Utilisateur);
        $_POST['id'] = $transactionId;

        $this->client->request('GET', 'modules/helios/helios_transac_submit.php');

        static::assertSame(HeliosTransactionsSQL::ATTENTE_POSTEE, $this->getLastStatusId($transactionId));
        static::assertSame('Accès refusé', $_SESSION['error']);
    }

    private function getLastStatusId(int $transactionId): int
    {
        return $this->heliosTransactionsSQL->getInfo($transactionId)['last_status_id'];
    }

    public function getHeliosTransactionsSQL(): HeliosTransactionsSQL
    {
        return $this->heliosTransactionsSQL;
    }

    /**
     * @return SplFileInfo
     */
    private function addPesAllerInErrorPath(): SplFileInfo
    {
        $heliosResponsesErrorPath = self::getContainer()->getParameter('app.helios_responses_error_path');
        $pesAller = new SplFileInfo($heliosResponsesErrorPath . uniqid('test') . '.xml');

        file_put_contents(
            $pesAller->getPathname(),
            file_get_contents(__DIR__ . '/../test/PHPUnit/helios/fixtures/pes_aller.xml')
        );
        return $pesAller;
    }
}
