<?php

declare(strict_types=1);

namespace IntegrationTests\EndpointsActeHtml;

use Exception;
use IntegrationTests\S2lowIntegrationTestCase;
use PHPUnit\ActesUtilitiesTestTrait;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\Database;
use S2lowLegacy\Lib\ObjectInstancierFactory;

class ActesIntegrationTest extends S2lowIntegrationTestCase
{
    use ActesUtilitiesTestTrait;

    private string $enveloppeInErrorPath = '';

    private ?ActesTransactionsSQL $actesTransactionsSQL;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actesTransactionsSQL = new ActesTransactionsSQL($this->sqlQuery);
    }
    protected function tearDown(): void
    {
        if ($this->enveloppeInErrorPath !== '' && file_exists($this->enveloppeInErrorPath)) {
            foreach (glob($this->enveloppeInErrorPath . '/*') as $file) {
                unlink($file);
            }
            rmdir($this->enveloppeInErrorPath);
        }
        $this->enveloppeInErrorPath = '';
        parent::tearDown();
    }

    protected function getActesTransactionsSQL(): ActesTransactionsSQL
    {
        return $this->actesTransactionsSQL;
    }

    /**
     * @throws Exception
     */
    public function testActesAnalyseResponse(): void
    {
        $client = $this->client;
        $this->setUserWithRole(UserRole::SuperAdministrateur);

        $enveloppeName = 'enveloppe';
        $this->copyEnveloppeToErrorDirectory($enveloppeName);
        $_GET['file'] = $enveloppeName; // Comme l'objet Récupérateur est set dans le script, ça ne fonctionne pas
        // autrement ( le client Symfony ne set pas _GET )
        $client->request(
            'GET',
            'modules/actes/admin/analyse-response.php'
        );
        static::assertMatchesRegularExpression(
            '#Le fichier a été analysé#',
            $_SESSION['error']
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws \Exception
     */
    public function testDeleteResponse(): void
    {
        $client = $this->client;
        $this->setUserWithRole(UserRole::SuperAdministrateur);

        $enveloppeName = 'enveloppe';
        $this->copyEnveloppeToErrorDirectory($enveloppeName);
        $_GET['file'] = $enveloppeName; // Comme l'objet Récupérateur est set dans le script, ça ne fonctionne pas
        // autrement ( le client Symfony ne set pas _GET )
        $client->request('GET', 'modules/actes/admin/delete-response.php');
        static::assertMatchesRegularExpression(
            '#Le fichier enveloppe a été supprimé#',
            $_SESSION['error']
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
        static::assertFileDoesNotExist($enveloppeName);
    }

    /**
     * @throws \Exception
     */
    public function testDownloadResponse(): void
    {
        $client = $this->client;
        $this->setUserWithRole(UserRole::SuperAdministrateur);

        $enveloppeName = 'enveloppe';
        $this->copyEnveloppeToErrorDirectory($enveloppeName);
        $_GET['file'] = $enveloppeName; // Comme l'objet Récupérateur est set dans le script, ça ne fonctionne pas
        // autrement ( le client Symfony ne set pas _GET )
        $crawler = $client->request('GET', 'modules/actes/admin/download-response.php');
        static::assertMatchesRegularExpression(
            '#enveloppe.tar.gz#',
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws Exception
     */
    public function testResponsesActesError(): void
    {
        $client = $this->client;
        $this->setUserWithRole(UserRole::SuperAdministrateur);

        $crawler = $client->request('GET', 'modules/actes/admin/responses-actes-error.php');
        static::assertMatchesRegularExpression(
            '#mails reçus en erreur#',
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws Exception
     */
    public function testActesStats(): void
    {
        $client = $this->client;
        $this->setUserWithRole(UserRole::SuperAdministrateur);

        $this->createTransaction(ActesStatusSQL::STATUS_TRANSMIS);
        $this->createTransaction(ActesStatusSQL::STATUS_POSTE);

        $crawler = $client->request('GET', 'modules/actes/actes_stats.php');

        static::assertMatchesRegularExpression(
            '#Nombre d\'enveloppes postées : 1#',
            $crawler->html()
        );

        static::assertMatchesRegularExpression(
            '#Nombre d\'enveloppes transmises au ministère : 1#',
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws Exception
     */
    public function testActesTransacArchiver(): void
    {
        $client = $this->client;
        $this->setUserWithRole(UserRole::SuperAdministrateur);

        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU);

        $_POST['id'] = $transaction_id;
        $client->request('GET', 'modules/actes/actes_transac_archiver.php');
        static::assertMatchesRegularExpression(
            '#La collectivité n\'a pas de Pastell configuré#',
            $_SESSION['error']
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws Exception
     */
    public function testActesTransacGetARActe(): void
    {
        $client = $this->client;
        $this->setUserWithRole(UserRole::SuperAdministrateur);

        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU);
        $_GET['id'] = $transaction_id;

        $crawler = $client->request('GET', 'modules/actes/actes_transac_get_ARActe.php');
        static::assertMatchesRegularExpression(
            '#Acquittement très officiel#',
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws Exception
     */
    public function testActesTransacRollBackAttente(): void
    {
        $client = $this->client;
        $this->setUserWithRole(UserRole::SuperAdministrateur);

        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU);
        $_POST['id'] = $transaction_id;

        $crawler = $client->request('GET', 'modules/actes/actes_transac_rolback_attente.php');
        static::assertMatchesRegularExpression(
            "#actes_transac_show.php\?id=$transaction_id#",
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
        static::assertSame(
            ActesStatusSQL::STATUS_EN_ATTENTE_DE_TRANSMISSION,
            $this->actesTransactionsSQL->getLastStatusInfo($transaction_id)['status_id'],
        );
    }

    /**
     * @throws Exception
     */
    public function testActesTransacSetError(): void
    {
        $client = $this->client;
        $this->setUserWithRole(UserRole::SuperAdministrateur);

        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU);
        $_POST['id'] = $transaction_id;

        $crawler = $client->request('GET', 'modules/actes/actes_transac_set_error.php');
        static::assertMatchesRegularExpression(
            "#actes_transac_show.php\?id=$transaction_id#",
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
        static::assertSame(
            ActesStatusSQL::STATUS_EN_ERREUR,
            $this->actesTransactionsSQL->getLastStatusInfo($transaction_id)['status_id'],
        );
    }

    /**
     * @throws Exception
     */
    public function testActesTransacShow(): void
    {
        $client = $this->client;
        $this->setUserWithRole(UserRole::SuperAdministrateur);

        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU);
        $_GET['id'] = $transaction_id;

        $crawler = $client->request('GET', 'modules/actes/actes_transac_show.php');
        static::assertMatchesRegularExpression(
            '#20170728C#',        //Le numéro de l'acte créé par ActesUtilitiesTestTrait.php
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * TODO : faire un cas plus réaliste
     * @throws Exception
     */
    public function testActesTransacSign(): void
    {
        $client = $this->client;
        $this->setUserWithRole(UserRole::SuperAdministrateur);

        $client->request('GET', 'modules/actes/actes_transac_sign.php');
        static::assertMatchesRegularExpression(
            '#Les signatures n\'ont pas pu être récupérées#',
            $_SESSION['error']
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws Exception
     */
    public function testActesIndex(): void
    {
        $client = $this->client;
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $this->createTransaction(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU);

        $crawler = $client->request('GET', 'modules/actes/index.php');
        static::assertMatchesRegularExpression(
            '#Liste des transactions - ACTES#',
            $crawler->html()
        );
        static::assertMatchesRegularExpression(
            '#20170728C#',        //Le numéro de l'acte créé par ActesUtilitiesTestTrait.php
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws Exception
     */
    public function testActesTransacClose(): void
    {
        $client = $this->client;
        $this->setUserWithRole(UserRole::SuperAdministrateur);

        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU);
        $_POST['id'] = $transaction_id;
        $_POST['status'] = 'sae';

        $crawler = $client->request('GET', 'modules/actes/actes_transac_close.php');
        static::assertMatchesRegularExpression(
            "#Message : Erreur lors de l'envoi de la transaction $transaction_id à Pastell#",
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    public function testActesTransacRepondreShowsFormForOwnTransaction(): void
    {
        $transactionId = $this->createTransactionOfType(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU, 3);
        $this->sqlQuery->query("INSERT INTO actes_type_pj (nature_id, code, libelle) VALUES (3, '99_AU', 'Autre')");
        $this->setUserWithRole(UserRole::Utilisateur);
        $_GET['id'] = $transactionId;

        $crawler = $this->client->request('GET', 'modules/actes/actes_transac_repondre.php');

        static::assertMatchesRegularExpression('#20170728C#', $crawler->html());
    }

    public function testActesTransacRepondreRefusesTransactionOfAnotherAuthority(): void
    {
        $userOfAnotherAuthority = 51;
        $transactionId = $this->createTransactionOfType(
            ActesStatusSQL::STATUS_ACQUITTEMENT_RECU,
            3,
            '',
            '2017-07-01',
            '2017-07-01',
            $userOfAnotherAuthority
        );
        $this->sqlQuery->query("INSERT INTO actes_type_pj (nature_id, code, libelle) VALUES (3, '99_AU', 'Autre')");
        $this->setUserWithRole(UserRole::Utilisateur);
        $_GET['id'] = $transactionId;

        $this->client->request('GET', 'modules/actes/actes_transac_repondre.php');

        static::assertSame('Accès refusé', $_SESSION['error']);
        static::assertStringNotContainsString('20170728C', $this->client->getResponse()->getContent());
    }

    /**
     * @param string $enveloppeName
     * @return void
     */
    private function copyEnveloppeToErrorDirectory(string $enveloppeName): void
    {
        $actesResponseErrorPath = self::getContainer()->getParameter('app.actes_response_error_path');
        $this->enveloppeInErrorPath = $actesResponseErrorPath . "/" . $enveloppeName . "/";

        mkdir($this->enveloppeInErrorPath);
        copy(
            __DIR__ . '/../../test/PHPUnit/class/fixtures/test-courrier-simple/034-000000000-20170701-20170725A-AI-2-1_0.xml',
            $this->enveloppeInErrorPath . '/034-000000000-20170701-20170725A-AI-2-1_0.xml'
        );
        copy(
            __DIR__ . '/../../test/PHPUnit/class/fixtures/test-courrier-simple/TACT--SPREF0011-000000000-20170725-1.xml',
            $this->enveloppeInErrorPath . '/TACT--SPREF0011-000000000-20170725-1.xml'
        );
        copy(
            __DIR__ . '/../../test/PHPUnit/class/fixtures/test-courrier-simple/034-000000000-20170701-20170725A-AI-2-1_1.pdf',
            $this->enveloppeInErrorPath . '/034-000000000-20170701-20170725A-AI-2-1_1.pdf'
        );
    }
}
