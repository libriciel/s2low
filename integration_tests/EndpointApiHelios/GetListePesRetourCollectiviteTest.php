<?php

namespace IntegrationTests\EndpointApiHelios;

use HeliosUtilitiesTestTrait;
use IntegrationTests\S2lowIntegrationTestCase;
use S2low\Enum\UserRole;
use S2lowLegacy\Model\HeliosTransactionsSQL;

class GetListePesRetourCollectiviteTest extends S2lowIntegrationTestCase
{
    use HeliosUtilitiesTestTrait;

    private HeliosTransactionsSQL $heliosTransactionsSQL;

    public function setUp(): void
    {
        parent::setUp();
        $this->heliosTransactionsSQL = new HeliosTransactionsSQL($this->getSQLQuery());
    }

    public function getHeliosTransactionsSQL(): HeliosTransactionsSQL
    {
        return $this->heliosTransactionsSQL;
    }

    public function testGetList(): void
    {
        $collectiviteId = 1;
        $filename = 'filename.xml';

        $_GET['collectivite'] = $collectiviteId;

        $this->addPESRetourToCollectivite($collectiviteId, $filename);

        $client = $this->client;
        $this->setUserWithRole(UserRole::Utilisateur);

        $client->request(
            'GET',
            '/modules/helios/api/helios_get_list.php',
            [
                'collectivite' => $collectiviteId,
            ],
        );

        $response = $client->getResponse();

        static::assertStringContainsString('<pes_retour>', $response->getContent());
        static::assertStringContainsString('<nom>filename.xml</nom>', $response->getContent());
    }
}
