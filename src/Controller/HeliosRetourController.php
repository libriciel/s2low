<?php

namespace S2low\Controller;

use Aws\Inspector\Exception\InspectorException;
use S2lowLegacy\Model\HeliosRetourSQL;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/helios-retour')]
class HeliosRetourController extends AbstractController
{
    public function __construct(
        private readonly HeliosRetourSQL $repository
    ) {
    }

    #[IsGranted("ROLE_SADM")]
    #[Route('/update/status', name: 'app_helios_retour_update_status', methods: ['POST'])]
    public function updateStatus(Request $request): Response
    {
        $ids = $request->request->all('ids');
        $status = (int) $request->request->get('status', HeliosRetourSQL::STATUS_NON_LU);
        $status = $status === HeliosRetourSQL::STATUS_LU ? HeliosRetourSQL::STATUS_LU : HeliosRetourSQL::STATUS_NON_LU;

        $this->repository->changeBulkStatus($ids, $status);

        return $this->redirectToRoute('app_legacy_modules_helios_helios_retour');
    }
}
