<?php

namespace IntegrationTests\EndpointActeAPI;

use IntegrationTests\S2lowIntegrationTestCase;
use PHPUnit\ActesUtilitiesTestTrait;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Lib\ObjectInstancierFactory;

class CloseTransactionTest extends S2lowIntegrationTestCase
{
    use ActesUtilitiesTestTrait;

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

    public function testShouldReturnOk(): void
    {
        $client = $this->getAuthenticatedClientWithUserLoggedAs(UserRole::Utilisateur);
        $transactionId = $this->createTransaction(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU);

        $api = 1;
        $status = 'valid';

        $_POST['api'] = $api;
        $_POST['id'] = $transactionId;
        $_POST['status'] = $status;

        $client->request('POST', '/modules/actes/actes_transac_close.php', [
            'api' => $api,
            'id' => $transactionId,
            'status' => $status,
        ]);

        $response = $client->getResponse();
        $content = explode("\n", trim($response->getContent()));

        static::assertSame('OK', $content[0]);
    }

    public function testShouldUpdateTransactionStatutToDenied(): void
    {
        $client = $this->getAuthenticatedClientWithUserLoggedAs(UserRole::Utilisateur);
        $transactionId = $this->createTransaction(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU);

        $api = 1;
        $id = $transactionId;
        $status = 'invalid';

        $_POST['id'] = $id;
        $_POST['status'] = $status;
        $_POST['api'] = $api;

        $client->request('POST', '/modules/actes/actes_transac_close.php', [
            'api' => $api,
            'id' => $id,
            'status' => $status,
        ]);

        $response = $client->getResponse();
        $content = explode("\n", trim($response->getContent()));

        static::assertSame('OK', $content[0]);
    }
}
