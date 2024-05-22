<?php

declare(strict_types=1);

namespace S2low\Controller;

use ActesTransaction;
use Exception;
use S2low\DTO\SAEStateTransitionRequest;
use S2low\Services\Actes\ActesSAEStateTransitionner;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;

class ActesSAEApiController extends AbstractController
{
    private Controller $legacyController;
    private ActesSAEStateTransitionner $transitionner;

    public function __construct(Controller $legacyController, ActesSAEStateTransitionner $transitionner)
    {
        $this->legacyController = $legacyController;
        $this->transitionner = $transitionner;
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
        try {
            $this->transitionner->do(
                $SAEStateTransitionRequest,
                $this->legacyController->getUser()->get('authority_id')
            );
            return $this->json(['status' => 'ok']);
        } catch (Exception $exception) {
            return $this->json(['error' => $exception->getMessage()], 400);
        }
    }
}
