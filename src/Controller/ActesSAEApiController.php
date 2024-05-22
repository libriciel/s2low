<?php

declare(strict_types=1);

namespace S2low\Controller;

use ActesTransaction;
use S2low\DTO\SAEStateTransitionRequest;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;

class ActesSAEApiController extends AbstractController
{
    private const ALLOWED_OUTPUT_STATUS = [
        ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE,
        ActesStatusSQL::STATUS_ARCHIVE_PAR_LE_SAE,
        ActesStatusSQL::STATUS_ENVOYE_AU_SAE,
        ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ARCHIVAGE,
        ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE
    ];
    private const ALLOWED_INPUT_STATUS = [
        ActesStatusSQL::STATUS_ACQUITTEMENT_RECU,
        ActesStatusSQL::STATUS_VALIDE,
        ActesStatusSQL::STATUS_ENVOYE_AU_SAE
    ];
    private Controller $legacyController;
    private ActesTransactionsSQL $actesTransactionsSQL;

    public function __construct(Controller $legacyController, ActesTransactionsSQL $actesTransactionsSQL)
    {
        $this->legacyController = $legacyController;
        $this->actesTransactionsSQL = $actesTransactionsSQL;
    }
    #[Route(
        path: '/modules/actes/api/actes_sae_status.php',
        name: 'app_api_v1_manage_actes_sae_status',
        methods: 'POST'
    )]
    public function manageSAEState(
        #[MapRequestPayload] SAEStateTransitionRequest $SAEStateTransitionRequest
    ): JsonResponse {
        $this->legacyController->verifUser();
        if (!$this->legacyController->getUser()->hasArchivistsRights()) {
            return $this->json(['error' => 'Pas les bons droits'], 400);
        }
        $trans = new ActesTransaction();
        $trans->setId($SAEStateTransitionRequest->transaction_id);
        if (! $trans->init()) {
            return $this->json(['error' => sprintf(
                'Transaction %s non existante',
                $SAEStateTransitionRequest->transaction_id
            )], 400);
        }
        if ($trans->get('authority_id') !== $this->legacyController->getUser()->get('authority_id')) {
            return $this->json(['error' => 'Mauvaise collectivite'], 400);
        }
        if (! in_array($trans->get('last_status_id'), self::ALLOWED_INPUT_STATUS, true)) {
            return $this->json(
                ['error' => sprintf(
                    'Transition depuis le statut %s impossible',
                    $trans->get('last_status_id')
                )],
                400
            );
        }
        if (!in_array($SAEStateTransitionRequest->status_id, self::ALLOWED_OUTPUT_STATUS, true)) {
            return $this->json(['error' => sprintf(
                'Transition vers le statut %s impossible',
                $SAEStateTransitionRequest->status_id
            )
            ], 400);
        }
        $this->actesTransactionsSQL->updateStatus(
            $SAEStateTransitionRequest->transaction_id,
            $SAEStateTransitionRequest->status_id,
            'Modification par l\'API manage_actes_sae_status'
        );
        return $this->json(['status' => 'ok']);
    }
}
