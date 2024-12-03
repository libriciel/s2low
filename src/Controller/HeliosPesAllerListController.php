<?php

namespace S2low\Controller;

use S2lowLegacy\Controller\Controller;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class HeliosPesAllerListController extends AbstractController
{
    public function __construct(private Controller $legacyController, private HeliosTransactionsSQL $heliosTransactionsSQL)
    {
    }
    /**
     * @Route(
     *     "/modules/helios/api/list_pes_aller.php",
     *     name="app_modules_helios_api_list_pes_aller",
     *     methods="GET"
     * )
     */
    public function list(): JsonResponse
    {
        $this->legacyController->verifUser();
        if (!$this->legacyController->getUser()->isArchivist()) {
            return $this->json(['error' => 'L\'utilisateur n\'est pas archiviste'], 400);
        }

        $status_id = $this->legacyController->getRecuperateurGet()->getInt('status_id');
        $offset = $this->legacyController->getRecuperateurGet()->getInt('offset');
        $limit = $this->legacyController->getRecuperateurGet()->getInt('limit', 100);
        $min_submission_date = $this->legacyController->getRecuperateurGet()->getDate('min_date');
        $max_submission_date = $this->legacyController->getRecuperateurGet()->getDate('max_date');

        $authority_id = intval($this->legacyController->getUser()->get('authority_id'));

        $transactions_list = $this->heliosTransactionsSQL->getListByStatusAndAuthority(
            $status_id,
            $authority_id,
            $offset,
            $limit,
            $min_submission_date,
            $max_submission_date
        );

        $result = [
            'status_id' => $status_id,
            'authority_id' => $authority_id,
            'offset' => $offset,
            'limit' => $limit,
            'transactions' => $transactions_list
        ];

        return new JsonResponse(legacy_encode_array($result));
    }
}
