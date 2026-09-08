<?php

declare(strict_types=1);

namespace S2low\Controller;

use S2low\Enum\AdministeredModule;
use S2lowLegacy\Model\AuthorityGroupSirenSQL;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Les SIREN qu'un formulaire de collectivité peut proposer pour un jeu de groupes administrateurs.
 *
 * Réservé au super administrateur : lui seul choisit ces groupes, et la réponse expose les SIREN
 * réservés par des groupes dont l'appelant n'est pas membre.
 */
class AvailableSirensController extends AbstractController
{
    public function __construct(
        private readonly AuthorityGroupSirenSQL $authorityGroupSirenSQL,
    ) {
    }

    #[Route('/api/authorities/available-sirens', name: 'api_authorities_available_sirens', methods: ['GET'])]
    #[IsGranted('ROLE_SADM')]
    public function availableSirens(Request $request): JsonResponse
    {
        $groupIds = [];

        foreach (AdministeredModule::cases() as $module) {
            $groupId = $request->query->getInt($module->groupColumn());
            if ($groupId !== 0) {
                $groupIds[] = $groupId;
            }
        }

        return new JsonResponse([
            'sirens' => $this->authorityGroupSirenSQL->getAvailableSirenForGroups(
                $groupIds,
                $request->query->getInt('authority_id')
            ),
        ]);
    }
}
