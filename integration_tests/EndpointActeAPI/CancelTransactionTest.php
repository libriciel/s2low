<?php

namespace IntegrationTests\EndpointActeAPI;

use IntegrationTests\S2lowIntegrationTestCase;
use PHPUnit\ActesUtilitiesTestTrait;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Lib\ObjectInstancierFactory;

class CancelTransactionTest extends S2lowIntegrationTestCase
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

    protected function dataprovider(): array
    {
        return [
            [true, 'OK'],
            [false, 'KO'],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testShouldReturnOk($isRealTransactionId, $stringInResponse): void
    {
        $this->setUserWithRole(UserRole::Utilisateur);
        $client = $this->client;

        if ($isRealTransactionId) {
            $transactionId = $this->createTransaction(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU);
        } else {
            $transactionId = 23664;
        }

        $api = 1;

        $_POST['api'] = $api;
        $_POST['id'] = $transactionId;

        $client->request('POST', '/modules/actes/actes_transac_cancel.php', [
            'api' => $api,
            'id' => $transactionId,
        ]);

        $response = $client->getResponse();
        static::assertStringContainsString($stringInResponse, $response->getContent());
    }
}
