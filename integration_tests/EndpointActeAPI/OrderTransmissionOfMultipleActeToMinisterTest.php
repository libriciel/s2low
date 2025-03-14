<?php

namespace IntegrationTests\EndpointActeAPI;

use IntegrationTests\S2lowIntegrationTestCase;
use PHPUnit\ActesUtilitiesTestTrait;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Lib\ObjectInstancierFactory;

class OrderTransmissionOfMultipleActeToMinisterTest extends S2lowIntegrationTestCase
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

    public function testShouldReturnOk(): void
    {
        $client = $this->getAuthenticatedClientWithUserLoggedAs(UserRole::Utilisateur);

        $firstTransactionId = $this->createTransaction(ActesStatusSQL::STATUS_EN_ATTENTE_D_ETRE_POSTE);
        $secondTransactionId = $this->createTransaction(ActesStatusSQL::STATUS_EN_ATTENTE_D_ETRE_POSTE);

        $ids[] = [$firstTransactionId, $secondTransactionId];
        $urlReturn = 'index.php';

        $_GET['id'] = $ids;
        $_GET['url_return'] = $urlReturn;

        $client->request('GET', '/modules/actes/actes_transac_post_confirm_api_multi.php', [
            'id[]' => $ids,
            'url_return' => $urlReturn,
        ]);

        $response = $client->getResponse();
        static::assertStringContainsString('Location:  index.php', $response->getContent());
    }
}
