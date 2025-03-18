<?php

namespace IntegrationTests\EndpointActeAPI;

use IntegrationTests\S2lowIntegrationTestCase;
use PHPUnit\ActesUtilitiesTestTrait;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Lib\ObjectInstancierFactory;

class GetTransactionStatusTest extends S2lowIntegrationTestCase
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

    protected function transactionStatusProvider(): array
    {
        return [
            [
                UserRole::Utilisateur,
                ActesStatusSQL::STATUS_ACQUITTEMENT_RECU,
                'Acquittement très officiel',
                null
            ],
            [
                UserRole::Utilisateur,
                ActesStatusSQL::STATUS_ACQUITTEMENT_RECU,
                'Numéro de transaction invalide',
                12314
            ],
            [
                UserRole::SuperAdministrateur,
                ActesStatusSQL::STATUS_ACQUITTEMENT_RECU,
                'KO',
                null
            ],
        ];
    }

    /**
     * @dataProvider transactionStatusProvider
     */
    public function testShouldReturnTransactionStatusCode(
        UserRole $userRole,
        $status,
        $shouldBeInHeader,
        $transactionId,
    ): void {
        $client = $this->getAuthenticatedClientWithUserLoggedAs($userRole);

        if ($transactionId === null) {
            $transactionId = $this->createTransaction($status);
        }

        $_GET['transaction'] = $transactionId;

        $client->request('GET', '/modules/actes/actes_transac_get_status.php', [
            'transaction' => $transactionId,
        ]);

        $response = $client->getResponse();

        static::assertStringContainsString($shouldBeInHeader, $response->getContent());
    }
}
