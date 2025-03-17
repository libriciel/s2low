<?php

namespace IntegrationTests\EndpointActeAPI;

use IntegrationTests\S2lowIntegrationTestCase;
use PHPUnit\ActesUtilitiesTestTrait;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;

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

    public function testShouldReturnOk(): void
    {
        $client = $this->getAuthenticatedClientWithUserLoggedAs(UserRole::SuperAdministrateur);
        $this->createTransaction(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU);

        $statusId = ActesStatusSQL::STATUS_ACQUITTEMENT_RECU;
        $offset = 0;
        $limit = 5;
        $minDate = (new \DateTimeImmutable('2000-01-01'))->format('Y-m-d');
        $maxDate = date('Y-m-d');

        $_GET['status_id'] = $statusId;
        $_GET['offset'] = $offset;
        $_GET['limit'] = $limit;
        $_GET['min_date'] = $minDate;
        $_GET['max_date'] = $maxDate;

        $client->request('GET', '/modules/actes/api/list_actes.php', [
            'status_id' => $statusId,
            'offset' => $offset,
            'limit' => $limit,
            'min_date' => $minDate,
            'max_date' => $maxDate,
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
        static::assertCount(1, $contentAsArray['transactions']);
    }

}
