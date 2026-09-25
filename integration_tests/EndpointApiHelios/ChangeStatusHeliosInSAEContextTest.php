<?php

namespace EndpointApiHelios;

use HeliosUtilitiesTestTrait;
use IntegrationTests\S2lowIntegrationTestCase;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\Database;
use S2lowLegacy\Model\HeliosTransactionsSQL;

class ChangeStatusHeliosInSAEContextTest extends S2lowIntegrationTestCase
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
                    'user_role' => UserRole::Archiviste,
                    'status_transaction_cree' => HeliosTransactionsSQL::INFORMATION_DISPONIBLE,
                    'status_transaction_cible' => HeliosTransactionsSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE,
                    'string_in_response' => '{"status":"ok","message":"Le status de la transaction a \u00e9t\u00e9 modifi\u00e9e"}',
                ]
            ],
            [
                [
                    'user_role' => UserRole::Archiviste,
                    'status_transaction_cree' => HeliosTransactionsSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE,
                    'status_transaction_cible' => HeliosTransactionsSQL::ACCEPTE_SAE,
                    'string_in_response' => '{"status":"ok","message":"Le status de la transaction a \u00e9t\u00e9 modifi\u00e9e"}',
                ]
            ],
            [
                [
                    'user_role' => UserRole::Archiviste,
                    'status_transaction_cree' => HeliosTransactionsSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE,
                    'status_transaction_cible' => HeliosTransactionsSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE,
                    'string_in_response' => '{"status":"ok","message":"Le status de la transaction a \u00e9t\u00e9 modifi\u00e9e"}',
                ]
            ],
            [
                [
                    'user_role' => UserRole::Archiviste,
                    'status_transaction_cree' => HeliosTransactionsSQL::ENVOYE_AU_SAE,
                    'status_transaction_cible' => HeliosTransactionsSQL::ACCEPTE_SAE,
                    'string_in_response' => '{"status":"ok","message":"Le status de la transaction a \u00e9t\u00e9 modifi\u00e9e"}',
                ]
            ],
            [
                [
                    'user_role' => UserRole::Archiviste,
                    'status_transaction_cree' => HeliosTransactionsSQL::ENVOYE_AU_SAE,
                    'status_transaction_cible' => HeliosTransactionsSQL::TRANSMIS,
                    'string_in_response' => '{"status":"error","error-message":"Impossible de changer le status de la transaction"}',
                ]
            ],
            [
                [
                    'user_role' => UserRole::Archiviste,
                    'status_transaction_cree' => HeliosTransactionsSQL::INFORMATION_DISPONIBLE,
                    'status_transaction_cible' => HeliosTransactionsSQL::ACCEPTE_SAE,
                    'string_in_response' => '{"status":"error","error-message":"Impossible de changer le status de la transaction"}',
                ]
            ],
            [
                [
                    'user_role' => UserRole::Utilisateur,
                    'status_transaction_cree' => HeliosTransactionsSQL::INFORMATION_DISPONIBLE,
                    'status_transaction_cible' => HeliosTransactionsSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE,
                    'string_in_response' => '{"status":"error","error-message":"Acc\u00e8s refus\u00e9"}',
                ]
            ],
            [
                [
                    'user_role' => UserRole::AdministrateurCollectivite,
                    'status_transaction_cree' => HeliosTransactionsSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE,
                    'status_transaction_cible' => HeliosTransactionsSQL::ACCEPTE_SAE,
                    'string_in_response' => '{"status":"ok","message":"Le status de la transaction a \u00e9t\u00e9 modifi\u00e9e"}',
                ]
            ],
            [
                [
                    'user_role' => UserRole::AdministrateurGroupe,
                    'status_transaction_cree' => HeliosTransactionsSQL::INFORMATION_DISPONIBLE,
                    'status_transaction_cible' => HeliosTransactionsSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE,
                    'string_in_response' => '{"status":"error","error-message":"Impossible de changer le status de la transaction"}',
                ]
            ],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testShouldChangeStatus($data): void
    {
        $this->setUserWithRole($data['user_role']);

        $transactionId = $this->createTransaction(
            1,
            $data['status_transaction_cree'],
        );

        $_POST['api'] = 1;
        $_POST['transaction_id'] = $transactionId;
        $_POST['status_id'] = $data['status_transaction_cible'];

        $client = $this->client;
        $client->request(
            'POST',
            '/modules/helios/helios_transac_change_status_sae.php',
            [
                'api' => 1,
                'transaction_id' => $transactionId,
                'status_id' => $data['status_transaction_cible'],
            ],
        );

        $response = $client->getResponse();

        static::assertStringContainsString($data['string_in_response'], $response->getContent());
    }

    public function testRefusesToChangeStatusOfTransactionOfAnotherAuthority(): void
    {
        $userOfAnotherAuthority = 51;
        $transactionId = $this->createTransaction(2, HeliosTransactionsSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE);
        $this->getSQLQuery()->query('UPDATE helios_transactions SET user_id = ? WHERE id = ?', $userOfAnotherAuthority, $transactionId);
        $this->setUserWithRole(UserRole::AdministrateurCollectivite);
        $postedData = ['api' => 1, 'transaction_id' => $transactionId, 'status_id' => HeliosTransactionsSQL::ACCEPTE_SAE];
        $_POST = $postedData;

        $this->client->request('POST', '/modules/helios/helios_transac_change_status_sae.php', $postedData);

        static::assertStringContainsString(
            '{"status":"error","error-message":"Acc\u00e8s refus\u00e9"}',
            $this->client->getResponse()->getContent()
        );
        static::assertSame(
            HeliosTransactionsSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE,
            $this->heliosTransactionsSQL->getLastStatusInfo($transactionId)['status_id']
        );
    }
}
