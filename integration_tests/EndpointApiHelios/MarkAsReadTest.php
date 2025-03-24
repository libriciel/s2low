<?php

namespace IntegrationTests\EndpointApiHelios;

use HeliosUtilitiesTestTrait;
use IntegrationTests\S2lowIntegrationTestCase;
use S2low\Enum\UserRole;
use S2lowLegacy\Model\HeliosTransactionsSQL;

class MarkAsReadTest extends S2lowIntegrationTestCase
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

    public function testShouldMarkAsRead(): void
    {
        $this->createUserWithDefaultCertificatAs(UserRole::Utilisateur);

        $collectiviteId = 1;
        $filename = 'filename.xml';

        $PESRetourId = $this->addPESRetourToCollectivite($collectiviteId, $filename);
        $_GET['id'] = $PESRetourId;

        $client = $this->getAuthenticatedClientAttachedToDefaultCertificat();
        $client->request(
            'GET',
            '/modules/helios/api/helios_change_status.php',
            [
                'id' => $PESRetourId,
            ],
        );

        $response = $client->getResponse();

        static::assertStringContainsString('OK', $response->getContent());
    }
}
