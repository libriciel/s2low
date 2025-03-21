<?php

namespace IntegrationTests\EndpointApiHelios;

use S2low\Enum\UserRole;
use HeliosUtilitiesTestTrait;
use IntegrationTests\S2lowIntegrationTestCase;
use S2lowLegacy\Model\HeliosTransactionsSQL;

class PosterUnDocumentComptableTest extends S2lowIntegrationTestCase
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

    protected function dataProvider(): array
    {
        return [
            [
                [
                    'status' => HeliosTransactionsSQL::ACQUITTE,
                    'statusEnReponse' => HeliosTransactionsSQL::TRANSMIS
                ]
            ],
            [
                [
                    'status' => HeliosTransactionsSQL::REFUSE,
                    'statusEnReponse' => HeliosTransactionsSQL::TRANSMIS
                ]
            ],
            [
                [
                    'status' => HeliosTransactionsSQL::INFORMATION_DISPONIBLE,
                    'statusEnReponse' => HeliosTransactionsSQL::TRANSMIS
                ]
            ],
            [
                [
                    'status' => HeliosTransactionsSQL::POSTE,
                    'statusEnReponse' => HeliosTransactionsSQL::POSTE
                ]
            ],
            [
                [
                    'status' => HeliosTransactionsSQL::ATTENTE,
                    'statusEnReponse' => HeliosTransactionsSQL::ATTENTE
                ]
            ],
            [
                [
                    'status' => HeliosTransactionsSQL::DETRUITE,
                    'statusEnReponse' => HeliosTransactionsSQL::DETRUITE
                ]
            ],
            [
                [
                    'status' => HeliosTransactionsSQL::EN_TRAITEMENT,
                    'statusEnReponse' => HeliosTransactionsSQL::EN_TRAITEMENT
                ]
            ],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testShouldReturnFile($data): void
    {
        $this->createUserWithDefaultCertificatAs(UserRole::Utilisateur);

        $transactionId = $this->createTransaction(
            1,
            $data['status'],
        );

        $_GET['transaction'] = $transactionId;

        $client = $this->getAuthenticatedClientAttachedToDefaultCertificat();
        $client->request(
            'GET',
            '/modules/helios/api/helios_transac_get_status.php',
            [
                'transaction' => $transactionId,
            ],
        );

        $response = $client->getResponse();

        static::assertStringContainsString('<resultat>OK</resultat>', $response->getContent());
        static::assertStringContainsString(
            '<status>' . $data['statusEnReponse'] . '</status>',
            $response->getContent()
        );
    }
}
