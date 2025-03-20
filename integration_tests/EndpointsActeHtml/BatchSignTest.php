<?php

namespace IntegrationTests\EndpointsActeHtml;

use IntegrationTests\S2lowIntegrationTestCase;
use PHPUnit\ActesUtilitiesTestTrait;
use S2low\Enum\ModulePermission;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Lib\ObjectInstancierFactory;

class BatchSignTest extends S2lowIntegrationTestCase
{
    use ActesUtilitiesTestTrait;

    private const BATCH_SIGN_ENDPOINT = "modules/actes/actes_batch_sign.php";

    private ?ActesTransactionsSQL $actesTransactionsSQL;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actesTransactionsSQL = new ActesTransactionsSQL($this->sqlQuery);
        ObjectInstancierFactory::resetObjectInstancier();
    }

    protected function getActesTransactionsSQL(): ActesTransactionsSQL
    {
        return $this->actesTransactionsSQL;
    }

    public function testShouldReturnSuccessResponse(): void
    {
        $client = $this->getAuthenticatedClientWithUserLoggedAs(UserRole::Utilisateur);
        $client->request('GET', self::BATCH_SIGN_ENDPOINT);

        static::assertResponseIsSuccessful();
    }

    public function testShouldRedirectIndexWithErrorMessage(): void
    {
        $client = $this->getAuthenticatedClientWithUserLoggedAs(UserRole::Utilisateur);

        $client->request('GET', self::BATCH_SIGN_ENDPOINT);
        $response = $client->getResponse();

        static::assertStringContainsString(
            'Vous devez sélectionner au moins une transaction à signer',
            $response->getContent()
        );
    }

    public function testShouldDisplayUiToSignActes(): void
    {
        $client = $this->getAuthenticatedClientWithUserLoggedAs(UserRole::Utilisateur, ModulePermission::Modification);
        $transactionId = $this->createTransaction(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU);
        $this->createActeIncludedFiles($transactionId);

        $_POST["liste_id"] = [$transactionId];

        $client->request(
            'POST',
            self::BATCH_SIGN_ENDPOINT,
            [
                'liste_id[]' => $transactionId,
            ]
        );

        $response = $client->getResponse();
        static::assertStringContainsString(
            'ACTES - Signature de plusieurs Actes',
            $response->getContent()
        );
    }

    public function testShouldErrorIfNotGoodPerms()
    {
        $client = $this->getAuthenticatedClientWithUserLoggedAs(UserRole::Utilisateur, ModulePermission::Visualisation);
        $client->request('GET', self::BATCH_SIGN_ENDPOINT);

        $response = $client->getResponse();

        static::assertStringContainsString(
            'Vous ne disposez pas du droit de signature',
            $response->getContent()
        );
    }
}
