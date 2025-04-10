<?php

namespace IntegrationTests\EndpointActeAPI;

use IntegrationTests\S2lowIntegrationTestCase;
use PHPUnit\ActesUtilitiesTestTrait;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\ActesWorkspaceForTests;
use S2lowLegacy\Lib\ObjectInstancierFactory;

class ChangeActeSaeStatusTest extends S2lowIntegrationTestCase
{
    use ActesUtilitiesTestTrait;

    private ?ActesTransactionsSQL $actesTransactionsSQL;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actesTransactionsSQL = new ActesTransactionsSQL($this->sqlQuery);
    }

    protected function tearDown(): void
    {
        $this->getActesWorkspace()->clear();
    }

    protected function getActesTransactionsSQL(): ActesTransactionsSQL
    {
        return $this->actesTransactionsSQL;
    }

    public function changeStatusProvider(): array
    {
        return [
            [
                UserRole::Archiviste,
                ActesStatusSQL::STATUS_ACQUITTEMENT_RECU,
                ActesStatusSQL::STATUS_ENVOYE_AU_SAE,
                'status',
                'ok',
                200
            ],
            [
                UserRole::Archiviste,
                ActesStatusSQL::STATUS_ACQUITTEMENT_RECU,
                ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ARCHIVAGE,
                'status',
                'ok',
                200
            ],
            [
                UserRole::Archiviste,
                ActesStatusSQL::STATUS_VALIDE,
                ActesStatusSQL::STATUS_ENVOYE_AU_SAE,
                'status',
                'ok',
                200
            ],
            [
                UserRole::Archiviste,
                ActesStatusSQL::STATUS_ENVOYE_AU_SAE,
                ActesStatusSQL::STATUS_ENVOYE_AU_SAE,
                'error',
                'Transition entre deux status identiques (12) impossible',
                400
            ],
            [
                UserRole::Utilisateur,
                ActesStatusSQL::STATUS_ACQUITTEMENT_RECU,
                ActesStatusSQL::STATUS_ENVOYE_AU_SAE,
                'error',
                'L\'utilisateur n\'est pas archiviste',
                400
            ],
            [
                UserRole::Archiviste,
                ActesStatusSQL::STATUS_VALIDE,
                ActesStatusSQL::STATUS_ACQUITTEMENT_RECU,
                'error',
                'Transition vers le statut 4 impossible',
                400
            ],
        ];
    }

    /**
     * @dataProvider changeStatusProvider
     */
    public function testActeStatusTransition(
        UserRole $userRole,
        int $acteStatusDepart,
        int $acteStatusDestination,
        string $arrayKeyAnswer,
        string $arrayValueAnswer,
        int $codeResponse
    ): void {
        $transactionId = $this->createTransaction($acteStatusDepart);

        $statusId = $acteStatusDestination;

        $_POST['transaction_id'] = $transactionId;
        $_POST['status_id'] = $statusId;

        $client = $this->getAuthenticatedClientWithUserLoggedAs($userRole);
        $client->request('POST', '/modules/actes/api/actes_sae_status.php', [
            'transaction_id' => $transactionId,
            'status_id' => $statusId,
        ]);

        $response = $client->getResponse();

        $content = explode("\n", trim($response->getContent()));

        static::assertJson($content[0]);
        $contentAsArray = json_decode($content[0], true);
        static::assertArrayHasKey($arrayKeyAnswer, $contentAsArray);
        static::assertSame($arrayValueAnswer, $contentAsArray[$arrayKeyAnswer]);
        static::assertResponseStatusCodeSame($codeResponse);
    }
}
