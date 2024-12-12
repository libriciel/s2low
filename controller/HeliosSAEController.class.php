<?php

namespace S2lowLegacy\Controller;

use Exception;
use S2lowLegacy\Class\actes\FilesNotFoundInCloudException;
use S2lowLegacy\Class\FailedControllerActionException;
use S2lowLegacy\Class\helios\HeliosEnvoiSAE;
use S2lowLegacy\Class\helios\HeliosStatusSQL;
use S2lowLegacy\Class\helios\HeliosVerificationSAE;
use S2lowLegacy\Lib\JSONoutput;
use S2lowLegacy\Lib\RedirectException;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use Symfony\Component\HttpFoundation\JsonResponse;

class HeliosSAEController extends Controller
{
    public const TRANSITIONS_DEFAUT = [
        HeliosStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE => [
            HeliosStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE,
            HeliosStatusSQL::ACCEPTER_PAR_LE_SAE
        ],
        HeliosStatusSQL::ENVOYER_AU_SAE => [
            HeliosStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE,
            HeliosStatusSQL::ACCEPTER_PAR_LE_SAE
        ],
        HeliosStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE => [
            HeliosStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE,
            HeliosStatusSQL::ACCEPTER_PAR_LE_SAE
        ]
    ];
    public const TRANSITIONS_ARCHIVIST = [
        HeliosTransactionsSQL::INFORMATION_DISPONIBLE => [
            HeliosStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE
        ]
    ];

    /**
     * @throws RedirectException
     */
    public function verificationAction()
    {
        $this->verifSuperAdmin();

        $transaction_id = $this->getRecuperateurPost()->get('transaction_id');

        try {
            $this->getObjectInstancier()->get(HeliosVerificationSAE::class)->verifArchiveThrow($transaction_id);
            $message = "La transaction a été traité par le SAE";
        } catch (Exception $e) {
            $message =  $e->getMessage();
        }

        $this->setMessage($message);
        $this->redirect("/modules/helios/helios_transac_show.php?id=$transaction_id");
    }

    /**
     * @throws RedirectException
     */
    public function sendSAEAction()
    {
        $this->verifSuperAdmin();

        $transaction_id = $this->getRecuperateurPost()->get('transaction_id');

        try {
            $this->getObjectInstancier()->get(HeliosEnvoiSAE::class)->sendArchiveThrow($transaction_id);
            $message = "La transaction a été envoyé sur le SAE";
        } catch (FilesNotFoundInCloudException $e) {
            $message = "Documents indisponibles pour la transaction $transaction_id  : " . $e->getMessage();
            $this->getObjectInstancier()->get(HeliosTransactionsSQL::class)->updateStatus(
                $transaction_id,
                HeliosStatusSQL::STATUS_ERREUR_SAE_DOC_INDISPONIBLES,
                $message
            );
        } catch (Exception $e) {
            $message =  $e->getMessage();
        }

        $this->setMessage($message);
        $this->redirect("/modules/helios/helios_transac_show.php?id=$transaction_id");
    }


    private function isActionPossible($statut_initial, $status_final, bool $isArchivist = false): bool
    {
        $statusFinauxPossibles = $this->getActionPossible($statut_initial, $isArchivist);
        return in_array($status_final, $statusFinauxPossibles);
    }

    /**
     * @throws RedirectException
     * @throws \Exception
     */
    public function changeStatusAction(): JsonResponse
    {
        $this->verifUser();

        try {
            $failed = true;
            if (!$this->me->isAdmin() && !$this->me->isArchivist()) {
                throw new FailedControllerActionException('Accès refusé', WEBSITE_SSL);
            }
            $transaction_id = $this->getRecuperateurPost()->get('transaction_id');
            $status_id = $this->getRecuperateurPost()->get('status_id');

        /** @var HeliosTransactionsSQL $heliosTransactionSQL */
            $heliosTransactionSQL = $this->getObjectInstancier()->get(HeliosTransactionsSQL::class);

            $status_info = $heliosTransactionSQL->getLastStatusInfo($transaction_id);
            $transaction_info = $heliosTransactionSQL->getInfo($transaction_id);


            if ($this->me->isArchivist() && $this->me->get('authority_id') != $transaction_info['authority_id']) {
                throw new FailedControllerActionException('Accès refusé', WEBSITE_SSL);
            }

            if (!$status_info) {
                throw new FailedControllerActionException("Cette transaction n'existe pas", '/',);
            }
            if ($this->isActionPossible($status_info['status_id'], $status_id, $this->me->isArchivist())) {
                $heliosTransactionSQL->updateStatus(
                    $transaction_id,
                    $status_id,
                    "Modification manuelle de l'état"
                );
                $failed = false;
                $message = 'Le status de la transaction a été modifiée';
            } else {
                $message = 'Impossible de changer le status de la transaction';
            }
            $redirectionUrl = "/modules/helios/helios_transac_show.php?id=$transaction_id";
        } catch (FailedControllerActionException $e) {
            $redirectionUrl = $e->getUrl();
            $message = $e->getMessage();
        }
        if (!$this->isApiCall()) {
            $this->redirect($redirectionUrl, $message);
        }
        $json = new JSONoutput();
        if ($failed) {
            $json->displayErrorAndExit($message);
        }
        $json->displayAndExit($message);
    }

    /**
     * @throws RedirectException
     */
    public function changeStatusBulkAction()
    {
        $this->verifSuperAdmin();
        $authority_id = $this->getRecuperateurPost()->get('authority_id');
        $status_id_from = $this->getRecuperateurPost()->get('status_id_from');
        $status_id_to = $this->getRecuperateurPost()->get('status_id_to');
        if (! $this->isActionPossible($status_id_from, $status_id_to)) {
            $this->setErrorMessage("Action impossible");
            $this->redirect("/admin/authorities/admin_authority_sae_statistiques.php?id=$authority_id");
        }

        $heliosTransactionsSQL = $this->getObjectInstancier()->get(HeliosTransactionsSQL::class);
        $all = $heliosTransactionsSQL->getIdsByStatus(
            $status_id_from,
            $authority_id
        );

        foreach ($all as $transaction_id) {
            $heliosTransactionsSQL->updateStatus(
                $transaction_id,
                $status_id_to,
                "Modification manuelle de l'état"
            );
        }

        $this->setMessage("L'état des transactions a été modifié");
        $this->redirect("/admin/authorities/admin_authority_sae_statistiques.php?id=$authority_id");
    }

    private function getTransitionsPossibles(bool $isArchivist): array
    {
        if (!$isArchivist) {
            return self::TRANSITIONS_DEFAUT;
        }
        return self::TRANSITIONS_DEFAUT + self::TRANSITIONS_ARCHIVIST;
    }

    /**
     * @param bool $isArchivist
     * @param $statut_initial
     * @return array
     */
    public function getActionPossible(int $statut_initial, bool $isArchivist = false): array
    {
        $transitionsPossibles = $this->getTransitionsPossibles($isArchivist);
        $statusFinauxPossibles = $transitionsPossibles[$statut_initial] ?? [];
        return $statusFinauxPossibles;
    }
}
