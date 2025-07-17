<?php

namespace IntegrationTests\EndpointActeAPI;

use IntegrationTests\S2lowIntegrationTestCase;
use PHPUnit\ActesUtilitiesTestTrait;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Lib\ObjectInstancierFactory;

class GetNumberOfActeWithSpecificStatusTest extends S2lowIntegrationTestCase
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

    protected function statusProvider(): array
    {
        return [
            [ActesStatusSQL::STATUS_TRANSMIS, 0],
            [ActesStatusSQL::STATUS_POSTE, 1],
            [ActesStatusSQL::STATUS_EN_ATTENTE_DE_TRANSMISSION, 2],
            [ActesStatusSQL::STATUS_EN_ATTENTE_D_ETRE_POSTE, 10],
            [ActesStatusSQL::STATUS_EN_ERREUR, 10],
        ];
    }

    /**
     * @dataProvider statusProvider
     */
    public function testShouldReturnGoodJson($status, $nbTransaction): void
    {
        for ($i = $nbTransaction; $i > 0; $i--) {
            $this->createTransaction($status);
        }

        $_GET['status_id'] = $status;

        $client = $this->client;
        $this->setUserWithRole(UserRole::Utilisateur);

        $client->request('GET', '/modules/actes/api/number_actes.php', [
            'status_id' => $status,
        ]);

        $response = $client->getResponse();
        $content = explode("\n", trim($response->getContent()));

        static::assertJson($content[0]);

        $contentAsArray = json_decode($content[0], true);

        static::assertArrayHasKey('status_id', $contentAsArray);
        static::assertArrayHasKey('authority_id', $contentAsArray);
        static::assertArrayHasKey('nb_transactions', $contentAsArray);

        static::assertSame($nbTransaction, $contentAsArray['nb_transactions']);
        static::assertSame($status, $contentAsArray['status_id']);
    }
}
