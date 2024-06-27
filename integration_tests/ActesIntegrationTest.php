<?php

declare(strict_types=1);

namespace IntegrationTests;

use Exception;
use S2lowLegacy\Lib\ObjectInstancierFactory;

class ActesIntegrationTest extends S2lowIntegrationTest
{
    /**
     * @throws Exception
     * TODO : corriger, bug dans cette fonctionnalité
     * https://gitlab.libriciel.fr/libriciel/pole-plate-formes/s2low/s2low/-/issues/1200
     */
    /*
    public function testActesForceClassification(): void
    {
        $certificatePem = $this->pemCertificateFactory->getFromString(
            file_get_contents(__DIR__ . '/../test/api/Eric_Pommateau_RGS_2_etoiles.pem')
        );

        $this->setUpUser($certificatePem->getContent(), $certificatePem->getHash());
        $client = $this->setUpClient(
            $certificatePem->getContent(),
            $certificatePem->getContentStrippedFromBegin()
        );

        ObjectInstancierFactory::resetObjectInstancier();

        $crawler = $client->request('GET', 'modules/actes/admin/actes_force_classifiction.php');
        static::assertMatchesRegularExpression(
            '#KO#',
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }
    */
    /**
     * TODO : ajouter un cas qui fonctionne
     * @throws Exception
     */
    public function testActesAnalyseResponse(): void
    {
        $certificatePem = $this->pemCertificateFactory->getFromString(
            file_get_contents(__DIR__ . '/../test/api/Eric_Pommateau_RGS_2_etoiles.pem')
        );

        $this->setUpUserInDB($certificatePem->getContent(), $certificatePem->getHash());
        $client = $this->setUpUserCertInServer(
            $certificatePem->getContent(),
            $certificatePem->getContentStrippedFromBegin()
        );

        ObjectInstancierFactory::resetObjectInstancier();

        $client->request('GET', 'modules/actes/admin/analyse-response.php');
        static::assertMatchesRegularExpression(
            '#Impossible de lire le fichier#',
            $_SESSION['error']
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * TODO : ajouter un cas qui fonctionne
     * @throws \Exception
     */
    public function testDeleteResponse(): void
    {
        $certificatePem = $this->pemCertificateFactory->getFromString(
            file_get_contents(__DIR__ . '/../test/api/Eric_Pommateau_RGS_2_etoiles.pem')
        );

        $this->setUpUserInDB($certificatePem->getContent(), $certificatePem->getHash());
        $client = $this->setUpUserCertInServer(
            $certificatePem->getContent(),
            $certificatePem->getContentStrippedFromBegin()
        );

        ObjectInstancierFactory::resetObjectInstancier();

        $client->request('GET', 'modules/actes/admin/analyse-response.php');
        static::assertMatchesRegularExpression(
            '#Impossible de lire le fichier#',
            $_SESSION['error']
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * TODO : ajouter un cas qui fonctionne
     * @throws \Exception
     */
    public function testDownloadResponse(): void
    {
        $certificatePem = $this->pemCertificateFactory->getFromString(
            file_get_contents(__DIR__ . '/../test/api/Eric_Pommateau_RGS_2_etoiles.pem')
        );

        $this->setUpUserInDB($certificatePem->getContent(), $certificatePem->getHash());
        $client = $this->setUpUserCertInServer(
            $certificatePem->getContent(),
            $certificatePem->getContentStrippedFromBegin()
        );

        ObjectInstancierFactory::resetObjectInstancier();

        $client->request('GET', 'modules/actes/admin/download-response.php');
        static::assertMatchesRegularExpression(
            '#Impossible de lire le fichier#',
            $_SESSION['error']
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws \Exception
     */
    public function testActesAdminIndex(): void
    {
        $certificatePem = $this->pemCertificateFactory->getFromString(
            file_get_contents(__DIR__ . '/../test/api/Eric_Pommateau_RGS_2_etoiles.pem')
        );

        $this->setUpUserInDB($certificatePem->getContent(), $certificatePem->getHash());
        $client = $this->setUpUserCertInServer(
            $certificatePem->getContent(),
            $certificatePem->getContentStrippedFromBegin()
        );

        ObjectInstancierFactory::resetObjectInstancier();

        $crawler = $client->request('GET', 'modules/actes/admin/index.php');
        static::assertMatchesRegularExpression(
            '#Utilitaires - ACTES#',
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * @throws Exception
     */
    public function testResponsesActesError(): void
    {
        $certificatePem = $this->pemCertificateFactory->getFromString(
            file_get_contents(__DIR__ . '/../test/api/Eric_Pommateau_RGS_2_etoiles.pem')
        );

        $this->setUpUserInDB($certificatePem->getContent(), $certificatePem->getHash());
        $client = $this->setUpUserCertInServer(
            $certificatePem->getContent(),
            $certificatePem->getContentStrippedFromBegin()
        );

        ObjectInstancierFactory::resetObjectInstancier();

        $crawler = $client->request('GET', 'modules/actes/admin/responses-actes-error.php');
        static::assertMatchesRegularExpression(
            '#mails reçus en erreur#',
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * TODO : ajouter un cas qui fonctionne
     * @throws Exception
     */
    public function testActesBatchSign(): void
    {
        $certificatePem = $this->pemCertificateFactory->getFromString(
            file_get_contents(__DIR__ . '/../test/api/Eric_Pommateau_RGS_2_etoiles.pem')
        );

        $this->setUpUserInDB($certificatePem->getContent(), $certificatePem->getHash());
        $client = $this->setUpUserCertInServer(
            $certificatePem->getContent(),
            $certificatePem->getContentStrippedFromBegin()
        );

        ObjectInstancierFactory::resetObjectInstancier();

        $crawler = $client->request('GET', 'modules/actes/actes_batch_sign.php');
        static::assertMatchesRegularExpression(
            '#Vous devez sélectionner au moins une transaction à signer#',
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * TODO : ajouter des transactions
     * @throws Exception
     */
    public function testActesStats(): void
    {
        $certificatePem = $this->pemCertificateFactory->getFromString(
            file_get_contents(__DIR__ . '/../test/api/Eric_Pommateau_RGS_2_etoiles.pem')
        );

        $this->setUpUserInDB($certificatePem->getContent(), $certificatePem->getHash());
        $client = $this->setUpUserCertInServer(
            $certificatePem->getContent(),
            $certificatePem->getContentStrippedFromBegin()
        );

        ObjectInstancierFactory::resetObjectInstancier();

        $crawler = $client->request('GET', 'modules/actes/actes_stats.php');
        static::assertMatchesRegularExpression(
            '#Statistiques de transmission des enveloppes  pour l\'ensemble des collectivités#',
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * TODO : ajouter des transactions
     * @throws Exception
     */
    public function testActesTransacArchiver(): void
    {
        $certificatePem = $this->pemCertificateFactory->getFromString(
            file_get_contents(__DIR__ . '/../test/api/Eric_Pommateau_RGS_2_etoiles.pem')
        );

        $this->setUpUserInDB($certificatePem->getContent(), $certificatePem->getHash());
        $client = $this->setUpUserCertInServer(
            $certificatePem->getContent(),
            $certificatePem->getContentStrippedFromBegin()
        );

        ObjectInstancierFactory::resetObjectInstancier();

        $client->request('GET', 'modules/actes/actes_transac_archiver.php');
        static::assertMatchesRegularExpression(
            '#Trying to access array offset on value of type bool#',
            $_SESSION['error']
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * TODO : ajouter des transactions
     * @throws Exception
     */
    public function testActesTransacDelete(): void
    {
        $certificatePem = $this->pemCertificateFactory->getFromString(
            file_get_contents(__DIR__ . '/../test/api/Eric_Pommateau_RGS_2_etoiles.pem')
        );

        $this->setUpUserInDB($certificatePem->getContent(), $certificatePem->getHash());
        $client = $this->setUpUserCertInServer(
            $certificatePem->getContent(),
            $certificatePem->getContentStrippedFromBegin()
        );

        ObjectInstancierFactory::resetObjectInstancier();

        $crawler = $client->request('GET', 'modules/actes/actes_transac_delete.php');
        static::assertMatchesRegularExpression(
            '#Invalid text representation:#',
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * TODO : ajouter des transactions
     * @throws Exception
     */
    public function testActesTransacGetARActe(): void
    {
        $certificatePem = $this->pemCertificateFactory->getFromString(
            file_get_contents(__DIR__ . '/../test/api/Eric_Pommateau_RGS_2_etoiles.pem')
        );

        $this->setUpUserInDB($certificatePem->getContent(), $certificatePem->getHash());
        $client = $this->setUpUserCertInServer(
            $certificatePem->getContent(),
            $certificatePem->getContentStrippedFromBegin()
        );

        ObjectInstancierFactory::resetObjectInstancier();

        $client->request('GET', 'modules/actes/actes_transac_get_ARActe.php');
        static::assertMatchesRegularExpression(
            '#Erreur d\'initialisation de la transaction#',
            $_SESSION['error']
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * TODO : ajouter des transactions
     * @throws Exception
     */
    public function testActesTransacPostConfirm(): void
    {
        $certificatePem = $this->pemCertificateFactory->getFromString(
            file_get_contents(__DIR__ . '/../test/api/Eric_Pommateau_RGS_2_etoiles.pem')
        );

        $this->setUpUserInDB($certificatePem->getContent(), $certificatePem->getHash());
        $client = $this->setUpUserCertInServer(
            $certificatePem->getContent(),
            $certificatePem->getContentStrippedFromBegin()
        );

        ObjectInstancierFactory::resetObjectInstancier();

        $client->request('GET', 'modules/actes/actes_transac_post_confirm.php');
        static::assertMatchesRegularExpression(
            '#La télétransmission nécessite un certificat RGS#',
            $_SESSION['error']
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * TODO : ajouter des transactions
     * @throws Exception
     */
    public function testActesTransacPostConfirmApiMulti(): void
    {
        $certificatePem = $this->pemCertificateFactory->getFromString(
            file_get_contents(__DIR__ . '/../test/api/Eric_Pommateau_RGS_2_etoiles.pem')
        );

        $this->setUpUserInDB($certificatePem->getContent(), $certificatePem->getHash());
        $client = $this->setUpUserCertInServer(
            $certificatePem->getContent(),
            $certificatePem->getContentStrippedFromBegin()
        );

        ObjectInstancierFactory::resetObjectInstancier();

            $crawler = $client->request('GET', 'modules/actes/actes_transac_post_confirm_api_multi.php');
            static::assertMatchesRegularExpression(
                '#exit\(\) called#',
                $crawler->html()
            );

        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * TODO : ajouter des transactions
     * @throws Exception
     */
    public function testActesTransacRollBackAttente(): void
    {
        $certificatePem = $this->pemCertificateFactory->getFromString(
            file_get_contents(__DIR__ . '/../test/api/Eric_Pommateau_RGS_2_etoiles.pem')
        );

        $this->setUpUserInDB($certificatePem->getContent(), $certificatePem->getHash());
        $client = $this->setUpUserCertInServer(
            $certificatePem->getContent(),
            $certificatePem->getContentStrippedFromBegin()
        );

        ObjectInstancierFactory::resetObjectInstancier();

        $crawler = $client->request('GET', 'modules/actes/actes_transac_rolback_attente.php');
        static::assertMatchesRegularExpression(
            '#Foreign key violation: 7 ERREUR#',        //TODO : faire un cas réaliste
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * TODO : ajouter des transactions
     * TODO : corriger ( le script ne fonctionne plus)
     * @throws Exception
     */
    public function testActesTransacSetError(): void
    {
        $certificatePem = $this->pemCertificateFactory->getFromString(
            file_get_contents(__DIR__ . '/../test/api/Eric_Pommateau_RGS_2_etoiles.pem')
        );

        $this->setUpUserInDB($certificatePem->getContent(), $certificatePem->getHash());
        $client = $this->setUpUserCertInServer(
            $certificatePem->getContent(),
            $certificatePem->getContentStrippedFromBegin()
        );

        ObjectInstancierFactory::resetObjectInstancier();

        $crawler = $client->request('GET', 'modules/actes/actes_transac_rolback_attente.php');
        static::assertMatchesRegularExpression(
            '#Foreign key violation: 7 ERREUR#',        //TODO : faire un cas réaliste
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * TODO : ajouter des transactions
     * @throws Exception
     */
    public function testActesTransacShow(): void
    {
        $certificatePem = $this->pemCertificateFactory->getFromString(
            file_get_contents(__DIR__ . '/../test/api/Eric_Pommateau_RGS_2_etoiles.pem')
        );

        $this->setUpUserInDB($certificatePem->getContent(), $certificatePem->getHash());
        $client = $this->setUpUserCertInServer(
            $certificatePem->getContent(),
            $certificatePem->getContentStrippedFromBegin()
        );

        ObjectInstancierFactory::resetObjectInstancier();

        $crawler = $client->request('GET', 'modules/actes/actes_transac_show.php');
        static::assertMatchesRegularExpression(
            '#exit\(\) called#',        //TODO : faire un cas réaliste
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * TODO : ajouter des transactions
     * @throws Exception
     */
    public function testActesTransacSign(): void
    {
        $certificatePem = $this->pemCertificateFactory->getFromString(
            file_get_contents(__DIR__ . '/../test/api/Eric_Pommateau_RGS_2_etoiles.pem')
        );

        $this->setUpUserInDB($certificatePem->getContent(), $certificatePem->getHash());
        $client = $this->setUpUserCertInServer(
            $certificatePem->getContent(),
            $certificatePem->getContentStrippedFromBegin()
        );

        ObjectInstancierFactory::resetObjectInstancier();

        $client->request('GET', 'modules/actes/actes_transac_sign.php');
        static::assertMatchesRegularExpression(
            '#Les signatures n\'ont pas pu être récupérées#',        //TODO : faire un cas réaliste
            $_SESSION['error']
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * TODO : ajouter des transactions
     * @throws Exception
     */
    public function testActesIndex(): void
    {
        $certificatePem = $this->pemCertificateFactory->getFromString(
            file_get_contents(__DIR__ . '/../test/api/Eric_Pommateau_RGS_2_etoiles.pem')
        );

        $this->setUpUserInDB($certificatePem->getContent(), $certificatePem->getHash());
        $client = $this->setUpUserCertInServer(
            $certificatePem->getContent(),
            $certificatePem->getContentStrippedFromBegin()
        );

        ObjectInstancierFactory::resetObjectInstancier();

        $_SERVER['QUERY_STRING'] = '';  // Autrement, ça ne fonctionne pas ...
        $crawler = $client->request('GET', 'modules/actes/index.php');
        static::assertMatchesRegularExpression(
            '#Liste des transactions - ACTES#',        //TODO : faire un cas réaliste
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }

    /**
     * TODO : ajouter des transactions
     * @throws Exception
     */
    public function testActesTransacClose(): void
    {
        $certificatePem = $this->pemCertificateFactory->getFromString(
            file_get_contents(__DIR__ . '/../test/api/Eric_Pommateau_RGS_2_etoiles.pem')
        );

        $this->setUpUserInDB($certificatePem->getContent(), $certificatePem->getHash());
        $client = $this->setUpUserCertInServer(
            $certificatePem->getContent(),
            $certificatePem->getContentStrippedFromBegin()
        );

        ObjectInstancierFactory::resetObjectInstancier();

        $_SERVER['QUERY_STRING'] = '';  // Autrement, ça ne fonctionne pas ...
        $crawler = $client->request('GET', 'modules/actes/actes_transac_close.php');
        static::assertMatchesRegularExpression(
            '#Message : État incorrect#',        //TODO : faire un cas réaliste
            $crawler->html()
        );
        static::assertResponseIsSuccessful();       // Aucune erreur lors de la requête
    }
}
