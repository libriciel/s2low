<?php

namespace IntegrationTests\EndpointActeAPI;

use IntegrationTests\S2lowIntegrationTestCase;
use PHPUnit\ActesUtilitiesTestTrait;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;

class GetNumberOfActeWithSpecificStatus extends S2lowIntegrationTestCase
{
    use ActesUtilitiesTestTrait;

    private ?ActesTransactionsSQL $actesTransactionsSQL;

    protected function getActesTransactionsSQL(): ActesTransactionsSQL
    {
        return $this->actesTransactionsSQL;
    }

    public function testShouldReturnOk(): void
    {
        $client = $this->getAuthenticatedClientWithUserLoggedAs(UserRole::Utilisateur);

        $statusId = 1;

        $_GET['status_id'] = $statusId;

        $client->request('GET', '/modules/actes/api/number_actes.php', [
            'status_id' => $statusId,
        ]);

        $response = $client->getResponse();
        $content = explode("\n", trim($response->getContent()));

        static::assertJson($content[0]);

        $contentAsArray = json_decode($content[0], true);

        static::assertArrayHasKey('status_id', $contentAsArray);
        static::assertArrayHasKey('authority_id', $contentAsArray);
        static::assertArrayHasKey('nb_transactions', $contentAsArray);
    }
}
