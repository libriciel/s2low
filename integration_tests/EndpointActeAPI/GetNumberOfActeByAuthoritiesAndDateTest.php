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

    public function testShouldReturnNumberOfTransactions(): void
    {
        $client = $this->getAuthenticatedClientWithUserLoggedAs(UserRole::SuperAdministrateur);
        $firstTransactionId = $this->createTransaction(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU);
        $secondTransactionId = $this->createTransaction(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU);
        $thirdTransactionId = $this->createTransaction(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU);

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
        $content = explode("\n", trim($response->getContent()));

        $contentAsArray = json_decode($content[0], true);
        static::assertJson($content[0]);

        static::assertArrayHasKey('result', $contentAsArray);
        static::assertArrayHasKey('authority_group_id', $contentAsArray);
        static::assertArrayHasKey('min_date', $contentAsArray);
        static::assertArrayHasKey('max_date', $contentAsArray);
        static::assertArrayHasKey('nbTransactionPerAuthorities', $contentAsArray);

        $nbTransactions = $contentAsArray['nbTransactionPerAuthorities'][0]['nb_transactions'];

        static::assertEquals(3, $nbTransactions);
    }
}
