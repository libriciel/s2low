<?php

declare(strict_types=1);

namespace S2low\Controller;

use ActesTransaction;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
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
        ActesStatusSQL::STATUS_VALIDE
    ];
    private Controller $legacyController;
    private ActesTransactionsSQL $actesTransactionsSQL;

    public function __construct(Controller $legacyController, ActesTransactionsSQL $actesTransactionsSQL)
    {
        $this->legacyController = $legacyController;
        $this->actesTransactionsSQL = $actesTransactionsSQL;
    }
    #[Route(path: '/modules/actes/api/actes_sae_status.php', name: 'manage_actes_sae_status')]
    public function manageSAEState(
        #[MapQueryParameter] int $transaction_id,
        #[MapQueryParameter] int $status_id
    ): Response {
        $this->legacyController->verifUser();
        if (!$this->legacyController->getUser()->hasArchivistsRights()) {
            return new Response(json_encode(['error' => 'Pas les bons droits']));
        }
        $trans = new ActesTransaction();
        $trans->setId($transaction_id);
        if (! $trans->init()) {
            return new Response(json_encode(['error' => "Transaction $transaction_id non existante"]));
        }
        if ($trans->get('authority_id') !== $this->legacyController->getUser()->get('authority_id')) {
            return new Response(json_encode(['error' => 'Mauvaise collectivite']));
        }
        if (! in_array($trans->get('last_status_id'), self::ALLOWED_INPUT_STATUS)) {
            return new Response(
                json_encode(['error' => "Transition depuis le statut {$trans->get('last_status_id')} impossible"])
            );
        }
        if (!in_array($status_id, self::ALLOWED_OUTPUT_STATUS)) {
            return new Response(json_encode(['error' => "Transition vers le statut $status_id impossible"]));
        }
        $this->actesTransactionsSQL->updateStatus(
            $transaction_id,
            $status_id,
            'Modification par l\'API manage_actes_sae_status'
        );
        return new Response(json_encode(['status' => 'ok']));
    }
}
