<?php

namespace IntegrationTests\EndpointsActe;

use IntegrationTests\S2lowIntegrationTestCase;
use PHPUnit\ActesUtilitiesTestTrait;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\User;
use S2lowLegacy\Lib\ObjectInstancierFactory;

class DeleteTransaction extends S2lowIntegrationTestCase
{
    private ?ActesTransactionsSQL $actesTransactionsSQL;

    use ActesUtilitiesTestTrait;


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

    public function testShouldDeleteTransaction(): void
    {
        $client = $this->getAuthenticatedClientWithUserLoggedAs(User::SADM);

        $transactionId = $this->createTransaction(ActesStatusSQL::STATUS_TRANSMIS);
        $_POST['id'] = $transactionId;
        $client->request('GET', 'modules/actes/actes_transac_delete.php');
        static::assertStringContainsString(
            "La transaction $transactionId a été éradiquée",
            $_SESSION['error']
        );
    }

}