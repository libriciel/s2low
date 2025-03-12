<?php

namespace IntegrationTests\EndpointActeAPI;

use IntegrationTests\S2lowIntegrationTestCase;
use PHPUnit\ActesUtilitiesTestTrait;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;

class UpdateClassificationTransactionTest extends S2lowIntegrationTestCase
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

        $api = 1;
        $_POST['api'] = $api;

        $client->request('POST', '/modules/actes/actes_classification_request.php', [
            'api' => $api,
        ]);

        $response = $client->getResponse();
        $content = explode("\n", trim($response->getContent()));

        static::assertSame('OK', $content[0]);
    }
}
