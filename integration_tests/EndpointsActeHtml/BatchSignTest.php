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
        $this->setUpWithoutDeletingObjectInstancier();
        $this->actesTransactionsSQL = ObjectInstancierFactory::getObjetInstancier()->get(ActesTransactionsSQL::class);
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
        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU);

        $_POST["liste_id"] = [$transaction_id];

        $client->request(
            'POST',
            self::BATCH_SIGN_ENDPOINT,
            [
                'liste_id[]' => $transaction_id,
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