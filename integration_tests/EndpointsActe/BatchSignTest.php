<?php

namespace IntegrationTests\EndpointsActe;

use IntegrationTests\S2lowIntegrationTestCase;
use PHPUnit\ActesUtilitiesTestTrait;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\User;
use S2lowLegacy\Lib\ObjectInstancierFactory;
use S2lowLegacy\Model\UsersPermsSQL;

class BatchSignTest extends S2lowIntegrationTestCase
{
    use ActesUtilitiesTestTrait;

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
        $client = $this->getAuthenticatedClientWithUserLoggedAs(User::USER);
        $client->request('GET', 'modules/actes/actes_batch_sign.php');

        static::assertResponseIsSuccessful();
    }

    public function testShouldRedirectIndexWithErrorMessage(): void
    {
        $client = $this->getAuthenticatedClientWithUserLoggedAs("USER");

        $client->request('GET', 'modules/actes/actes_batch_sign.php');
        $response = $client->getResponse();

        static::assertStringContainsString(
            'Vous devez sélectionner au moins une transaction à signer', $response->getContent()
        );

    }

    public function testShouldDisplayUiToSignActes(): void
    {
        $client = $this->getAuthenticatedClientWithUserLoggedAs(User::USER, UsersPermsSQL::PERM_MODIFICATION);
        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU);

        $_POST["liste_id"] = [$transaction_id];

        $client->request(
            'POST',
            'modules/actes/actes_batch_sign.php',
            [
                'liste_id[]' => $transaction_id,
            ]
        );

        $response = $client->getResponse();

        static::assertStringContainsString(
            'ACTES - Signature de plusieurs Actes', $response->getContent()
        );
    }

    public function testShouldErrorIfNotGoodPerms()
    {
        $client = $this->getAuthenticatedClientWithUserLoggedAs(User::USER, UsersPermsSQL::PERM_VISUALISATION);
        $client->request('GET', 'modules/actes/actes_batch_sign.php');

        $response = $client->getResponse();

        static::assertStringContainsString(
            'Vous ne disposez pas du droit de signature', $response->getContent()
        );
    }
}