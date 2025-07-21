<?php

namespace IntegrationTests\EndpointActeAPI;

use IntegrationTests\S2lowIntegrationTestCase;
use PHPUnit\ActesUtilitiesTestTrait;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
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
        $this->setUserWithRole(UserRole::Utilisateur);
        $api = 1;
        $_POST['api'] = $api;

        ob_start();

        $this->client->request('POST', '/modules/actes/actes_classification_fetch.php', [
            'api' => $api,
        ]);

        $content = ob_get_contents();
        ob_end_clean();

        static::assertTrue(str_contains($content, 'Content-type: text/xml'));
    }
}
