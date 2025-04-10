<?php

namespace IntegrationTests\EndpointActeAPI;

use IntegrationTests\S2lowIntegrationTestCase;
use PHPUnit\ActesUtilitiesTestTrait;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\ActesWorkspaceForTests;
use Symfony\Component\HttpFoundation\Response;

class GetClassificationXMLTest extends S2lowIntegrationTestCase
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

    public function testShouldReturnXML(): void
    {
        $api = 1;
        $_POST['api'] = $api;

        $client = $this->getAuthenticatedClientWithUserLoggedAs(UserRole::Utilisateur);
        $client->request('POST', '/modules/actes/actes_classification_fetch.php', [
            'api' => $api,
        ]);

        $response = $client->getResponse();

        static::assertTrue($this->headerReturnXMLFile($response));
    }

    private function headerReturnXMLFile(Response $response): bool
    {
        return str_contains($response->getContent(), 'Content-type: text/xml');
    }

    public function getWorkspace(): ActesWorkspaceForTests
    {
        return $this->getActesWorkspace();
    }
}
