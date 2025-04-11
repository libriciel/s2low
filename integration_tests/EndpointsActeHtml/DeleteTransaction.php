<?php

namespace IntegrationTests\EndpointsActeHtml;

use IntegrationTests\S2lowIntegrationTestCase;
use PHPUnit\ActesUtilitiesTestTrait;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Lib\ObjectInstancierFactory;

class DeleteTransaction extends S2lowIntegrationTestCase
{
    use ActesUtilitiesTestTrait;

    private ?ActesTransactionsSQL $actesTransactionsSQL;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getActesTransactionsSQL(): ActesTransactionsSQL
    {
        return ObjectInstancierFactory::getObjetInstancier()->get(ActesTransactionsSQL::class);
    }

    public function testShouldDeleteTransaction(): void
    {
        $client = $this->getAuthenticatedClientWithUserLoggedAs(UserRole::SuperAdministrateur);

        $transactionId = $this->createTransaction(ActesStatusSQL::STATUS_TRANSMIS);

        $_POST['id'] = $transactionId;
        $client->request('GET', 'modules/actes/actes_transac_delete.php');

        static::assertTrue($this->transactionIsDeleted($transactionId));
    }

    private function transactionIsDeleted($transactionId): bool
    {
        $transaction = $this->getActesTransactionsSQL()->getInfo($transactionId);

        return $transaction == false;
    }
}
