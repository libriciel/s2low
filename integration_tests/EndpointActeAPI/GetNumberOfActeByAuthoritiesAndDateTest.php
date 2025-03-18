<?php

namespace IntegrationTests\EndpointActeAPI;

use IntegrationTests\S2lowIntegrationTestCase;
use PHPUnit\ActesUtilitiesTestTrait;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Lib\ObjectInstancierFactory;

class GetNumberOfActeByAuthoritiesAndDateTest extends S2lowIntegrationTestCase
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

    protected function dataProvider(): array
    {
        return [
            [
                [
                    'monthToRequest' => 7,
                    'yearToRequest' => 2017,
                    'nbTransactionToCreate' => 1,
                    'nbTransactionToReturn' => 1,
                ]
            ],
            [
                [
                    'monthToRequest' => 7,
                    'yearToRequest' => 2017,
                    'nbTransactionToCreate' => 3,
                    'nbTransactionToReturn' => 3,
                ]
            ],
            [
                [
                    'monthToRequest' => 5,
                    'yearToRequest' => 2017,
                    'nbTransactionToCreate' => 2,
                    'nbTransactionToReturn' => 0,
                ]
            ]
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testShouldReturnNumberOfTransactions($data): void
    {
        $client = $this->getAuthenticatedClientWithUserLoggedAs(UserRole::SuperAdministrateur);

        for ($i = $data['nbTransactionToCreate']; $i > 0; $i--) {
            $this->createTransaction(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU);
        }

        $collectiviteGroupId = 1;
        $month = $data['monthToRequest'];
        $year = $data['yearToRequest'];

        $_GET['month'] = $month;
        $_GET['year'] = $year;

        $client->request('GET', '/modules/actes/api/nb_actes_by_authorities_and_date.php', [
            'authority_group_id' => $collectiviteGroupId,
            'month' => $month,
            'year' => $year,
        ]);

        $response = $client->getResponse();
        $content = explode("\n", trim($response->getContent()));
        $contentAsArray = json_decode($content[0], true);
        static::assertJson($content[0]);
        static::assertArrayHasKey('result', $contentAsArray);
        static::assertArrayHasKey('authority_group_id', $contentAsArray);
        static::assertArrayHasKey('min_date', $contentAsArray);
        static::assertArrayHasKey('min_date', $contentAsArray);

        if ($data['nbTransactionToReturn'] === 0) {
            static::assertCount(0, $contentAsArray['nbTransactionPerAuthorities']);
        } else {
            static::assertEquals(
                $data['nbTransactionToReturn'],
                $contentAsArray['nbTransactionPerAuthorities'][0]['nb_transactions']
            );
        }
    }

    public function testShouldExitIfNotAdmin(): void
    {
        $client = $this->getAuthenticatedClientWithUserLoggedAs(UserRole::Utilisateur);
        $this->createTransaction(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU);

        $collectiviteGroupId = 1;
        $month = 7;
        $year = 2017;

        $_GET['authority_group_id'] = $collectiviteGroupId;
        $_GET['month'] = $month;
        $_GET['year'] = $year;

        $client->request('GET', '/modules/actes/api/nb_actes_by_authorities_and_date.php', [
            'authority_group_id' => $collectiviteGroupId,
            'month' => $month,
            'year' => $year,
        ]);

        $response = $client->getResponse();
        static::assertStringContainsString('exit() called', $response->getContent());
    }
}
