<?php

namespace IntegrationTests\EndpointActeAPI;

use IntegrationTests\S2lowIntegrationTestCase;
use PHPUnit\ActesUtilitiesTestTrait;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;

class OrderTransmissionOfMultipleActeToMinisterTest extends S2lowIntegrationTestCase
{
    use ActesUtilitiesTestTrait;

    private ?ActesTransactionsSQL $actesTransactionsSQL;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actesTransactionsSQL = new ActesTransactionsSQL($this->sqlQuery);
    }

    protected function getActesTransactionsSQL(): ActesTransactionsSQL
    {
        return $this->actesTransactionsSQL;
    }

    public function testShouldReturnRightHeader(): void
    {
        $firstTransactionId = $this->createTransaction(ActesStatusSQL::STATUS_EN_ATTENTE_D_ETRE_POSTE);
        $secondTransactionId = $this->createTransaction(ActesStatusSQL::STATUS_EN_ATTENTE_D_ETRE_POSTE);

        $ids = [$firstTransactionId, $secondTransactionId];
        $urlReturn = 'index.php';

        $_GET['id'] = $ids;
        $_GET['url_return'] = $urlReturn;

        $client = $this->client;
        $this->setUserWithRole(UserRole::Utilisateur);

        $client->request('GET', '/modules/actes/actes_transac_post_confirm_api_multi.php', [
            'id[]' => $ids,
            'url_return' => $urlReturn,
        ]);

        $response = $client->getResponse();
        static::assertStringContainsString('index.php', $response->headers->get('Location'));
    }
}
