<?php

namespace IntegrationTests\EndpointActeAPI;

use IntegrationTests\S2lowIntegrationTestCase;
use PHPUnit\ActesUtilitiesTestTrait;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\ActesWorkspaceForTests;

class GetActesOfSpecificStatusTest extends S2lowIntegrationTestCase
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

    protected function dataProvider(): array
    {
        return [
            [
                [
                    'offset' => 0,
                    'limit' => 5,
                    'minDate' => (new \DateTimeImmutable('2000-01-01'))->format('Y-m-d'),
                    'maxDate' => date('Y-m-d'),
                    'nbTransactionToCreate' => 1,
                    'nbTransactionToReturn' => 1
                ]
            ],
            [
                [
                    'offset' => 1,
                    'limit' => 5,
                    'minDate' => (new \DateTimeImmutable('2000-01-01'))->format('Y-m-d'),
                    'maxDate' => date('Y-m-d'),
                    'nbTransactionToCreate' => 5,
                    'nbTransactionToReturn' => 4
                ]
            ],
            [
                [
                    'offset' => 0,
                    'limit' => 5,
                    'minDate' => (new \DateTimeImmutable('2100-01-01'))->format('Y-m-d'),
                    'maxDate' => date('Y-m-d'),
                    'nbTransactionToCreate' => 5,
                    'nbTransactionToReturn' => 0
                ]
            ],
            [
                [
                    'offset' => 0,
                    'limit' => 5,
                    'minDate' => (new \DateTimeImmutable('2000-01-01'))->format('Y-m-d'),
                    'maxDate' => (new \DateTimeImmutable('2001-01-01'))->format('Y-m-d'),
                    'nbTransactionToCreate' => 5,
                    'nbTransactionToReturn' => 0
                ]
            ],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testShouldReturnOk($data): void
    {
        $this->createTransaction(ActesStatusSQL::STATUS_VALIDE);

        for ($i = $data['nbTransactionToCreate']; $i > 0; $i--) {
            $this->createTransaction(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU);
        }

        $statusId = ActesStatusSQL::STATUS_ACQUITTEMENT_RECU;

        $_GET['status_id'] = $statusId;
        $_GET['offset'] = $data['offset'];
        $_GET['limit'] = $data['limit'];
        $_GET['min_date'] = $data['minDate'];
        $_GET['max_date'] = $data['maxDate'];

        $client = $this->getAuthenticatedClientWithUserLoggedAs(UserRole::SuperAdministrateur);
        $client->request('GET', '/modules/actes/api/list_actes.php', [
            'status_id' => $statusId,
            'offset' => $data['offset'],
            'limit' => $data['limit'],
            'min_date' => $data['minDate'],
            'max_date' => $data['maxDate'],
        ]);

        $response = $client->getResponse();
        $content = explode("\n", trim($response->getContent()));


        static::assertJson($content[0]);

        $contentAsArray = json_decode($content[0], true);
        static::assertArrayHasKey('status_id', $contentAsArray);
        static::assertArrayHasKey('authority_id', $contentAsArray);
        static::assertArrayHasKey('offset', $contentAsArray);
        static::assertArrayHasKey('limit', $contentAsArray);
        static::assertArrayHasKey('transactions', $contentAsArray);
        static::assertCount($data['nbTransactionToReturn'], $contentAsArray['transactions']);

        static::assertSame($statusId, intVal($contentAsArray['status_id']));
        static::assertSame($data['offset'], intVal($contentAsArray['offset']));
        static::assertSame($data['limit'], intVal($contentAsArray['limit']));
    }

    public function getWorkspace(): ActesWorkspaceForTests
    {
        return $this->getActesWorkspace();
    }
}
