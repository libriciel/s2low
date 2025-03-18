<?php

namespace IntegrationTests\EndpointActeAPI;

use IntegrationTests\S2lowIntegrationTestCase;
use PHPUnit\ActesUtilitiesTestTrait;
use S2low\Enum\UserRole;
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
        ObjectInstancierFactory::resetObjectInstancier();
    }

    protected function getActesTransactionsSQL(): ActesTransactionsSQL
    {
        return $this->actesTransactionsSQL;
    }

    protected function statusProvider(): array
    {
        $TRANSMI = 3;
        $POSTE = 1;
        $EN_ATTENTE_TRANSMISSION = 2;
        $EN_ATTENTRE_D_ETRE_POSTE = 17;
        $EN_ERREUR = -1;

        return [
            [$TRANSMI, 0],
            [$POSTE, 1],
            [$EN_ATTENTE_TRANSMISSION, 2],
            [$EN_ATTENTRE_D_ETRE_POSTE, 10],
            [$EN_ERREUR, 10],
        ];
    }

    /**
     * @dataProvider statusProvider
     */
    public function testShouldReturnGoodJson($status, $nbTransaction): void
    {
        $client = $this->getAuthenticatedClientWithUserLoggedAs(UserRole::Utilisateur);

        for ($i = $nbTransaction; $i > 0; $i--) {
            $this->createTransaction($status);
        }

        $_GET['status_id'] = $status;

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

        static::assertEquals($nbTransaction, $contentAsArray['nb_transactions']);
        static::assertEquals($status, $contentAsArray['status_id']);
    }
}
