<?php

declare(strict_types=1);

namespace IntegrationTests;

use Exception;
use HeliosUtilitiesTestTrait;
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
        foreach (glob(HELIOS_RESPONSES_ERROR_PATH . '/*') as $file) {
            unlink($file);
        }
    }

    /**
     * @throws Exception
     */
    public function testHeliosDeleteResponse(): void
    {
        $client = $this->getAuthenticatedClientWithSAdminUser();

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
        $client = $this->getAuthenticatedClientWithSAdminUser();
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
        $client = $this->getAuthenticatedClientWithSAdminUser();
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
        $client = $this->getAuthenticatedClientWithSAdminUser();
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
        $client = $this->getAuthenticatedClientWithSAdminUser();
        $this->createTransaction(
            1,
            HeliosTransactionsSQL::TRANSMIS,
            '01-01-1970'
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
        $client = $this->getAuthenticatedClientWithSAdminUser();

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
        $client = $this->getAuthenticatedClientWithSAdminUser();
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
        $client = $this->getAuthenticatedClientWithSAdminUser();
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
        $client = $this->getAuthenticatedClientWithSAdminUser();
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
        $client = $this->getAuthenticatedClientWithSAdminUser();
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
        $client = $this->getAuthenticatedClientWithSAdminUser();
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
        $client = $this->getAuthenticatedClientWithSAdminUser();

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
        $client = $this->getAuthenticatedClientWithSAdminUser();
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
        $client = $this->getAuthenticatedClientWithSAdminUser();
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

    public function getHeliosTransactionsSQL(): HeliosTransactionsSQL
    {
        return $this->heliosTransactionsSQL;
    }

    /**
     * @return SplFileInfo
     */
    private function addPesAllerInErrorPath(): SplFileInfo
    {
        $pesAller = new SplFileInfo(HELIOS_RESPONSES_ERROR_PATH . uniqid('test') . '.xml');

        file_put_contents(
            $pesAller->getPathname(),
            file_get_contents(__DIR__ . '/../test/PHPUnit/helios/fixtures/pes_aller.xml')
        );
        return $pesAller;
    }
}
