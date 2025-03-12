<?php

namespace IntegrationTests\EndpointActeAPI;

use IntegrationTests\S2lowIntegrationTestCase;
use PHPUnit\ActesUtilitiesTestTrait;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;

class GetNewUniqueEnveloppeNumber extends S2lowIntegrationTestCase
{
    use ActesUtilitiesTestTrait;

    private ?ActesTransactionsSQL $actesTransactionsSQL;

    protected function getActesTransactionsSQL(): ActesTransactionsSQL
    {
        return $this->actesTransactionsSQL;
    }

    public function testShouldUpdateClassification(): void
    {
        $client = $this->getAuthenticatedClientWithUserLoggedAs(UserRole::Utilisateur);

        $client->request('GET', '/modules/actes/actes_transac_get_env_serial.php');

        $response = $client->getResponse();
        $content = $response->getContent();

        $response = $client->getResponse();
        $content = explode("\n", trim($response->getContent()));

        static::assertSame('OK', $content[0]);
        static::assertNotEmpty($content[1]);
    }
}
