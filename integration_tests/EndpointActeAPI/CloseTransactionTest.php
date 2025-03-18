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

    public function changeStatusProvider(): array
    {
        return [
            [
                'valid',
                'OK'
            ],
            [
                'invalid',
                'OK'
            ],
            [
                'fgdg',
                'KO'
            ],
        ];
    }

    /**
     * @dataProvider changeStatusProvider
     */
    public function testShouldReturnOk($status, $responseMsg): void
    {
        $client = $this->getAuthenticatedClientWithUserLoggedAs(UserRole::Utilisateur);
        $transactionId = $this->createTransaction(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU);

        $api = 1;

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

        static::assertSame($responseMsg, $content[0]);
    }
}
