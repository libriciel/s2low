<?php

class ActesSAEController extends Controller
{
    /**
     * @throws RedirectException
     */
    public function sendAction()
    {
        $this->verifSuperAdmin();

        $transaction_id = $this->getRecuperateurPost()->get('transaction_id');

        try {
            $this->getObjectInstancier()->get(ActesArchiveControler::class)->sendArchiveThrow($transaction_id);
            $message = "La transaction a été envoyé au SAE";
        } catch (Exception $e) {
            $message =  $e->getMessage();
        }

        $this->setMessage($message);
        $this->redirect("/modules/actes/actes_transac_show.php?id=$transaction_id");
    }

    /**
     * @throws RedirectException
     */
    public function verifAction()
    {
        $this->verifSuperAdmin();

        $transaction_id = $this->getRecuperateurPost()->get('transaction_id');

        try {
            $r = $this->getObjectInstancier()->get(ActesVerifSaeWorker::class)->verifArchiveThrow($transaction_id);
            if ($r) {
                $message = "La transaction a été vérifié sur le SAE";
            } else {
                $message = "La transaction n'a pas encore été traité par le SAE";
            }
        } catch (Exception $e) {
            $message =  $e->getMessage();
        }

        $this->setMessage($message);
        $this->redirect("/modules/actes/actes_transac_show.php?id=$transaction_id");
    }

    public function getActionPossible($status)
    {
        $all_status = [
            ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE => [
                ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE,
                ActesStatusSQL::STATUS_ARCHIVE_PAR_LE_SAE
            ],
            ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE => [
                ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE,
                ActesStatusSQL::STATUS_ARCHIVE_PAR_LE_SAE
            ],
            ActesStatusSQL::STATUS_ENVOYE_AU_SAE => [
                ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE,
                ActesStatusSQL::STATUS_ARCHIVE_PAR_LE_SAE,
                ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE,
            ],
            ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ARCHIVAGE => [
                ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE,
                ActesStatusSQL::STATUS_ARCHIVE_PAR_LE_SAE,
                ActesStatusSQL::STATUS_ENVOYE_AU_SAE
            ]
        ];
        return $all_status[$status] ?? [];
    }

    private function isActionPossible($statut_initial, $status_final)
    {
        return in_array($status_final, $this->getActionPossible($statut_initial));
    }

    /**
     * @throws RedirectException
     */
    public function changeStatusAction()
    {
        $this->verifSuperAdmin();
        $transaction_id = $this->getRecuperateurPost()->get('transaction_id');
        $status_id = $this->getRecuperateurPost()->get('status_id');

        $actesTransactionSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);

        $status_info = $actesTransactionSQL->getLastStatusInfo($transaction_id);

        if (! $status_info) {
            $this->redirect("/", "Cette transaction n'existe pas");
        }

        if ($this->isActionPossible($status_info['status_id'], $status_id)) {
            $actesTransactionSQL->updateStatus(
                $transaction_id,
                $status_id,
                "Modification manuelle de l'état"
            );
            $this->setMessage("Le status de la transaction a été modifiée");
        } else {
            $this->setErrorMessage("Impossible de changer le status de la transaction");
        }

        $this->redirect("/modules/actes/actes_transac_show.php?id=$transaction_id");
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

        $actesTransactionsSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
        $all = $actesTransactionsSQL->getIdByStatusAndAuthority(
            $status_id_from,
            $authority_id
        );

        foreach ($all as $transaction_id) {
            $actesTransactionsSQL->updateStatus(
                $transaction_id,
                $status_id_to,
                "Modification manuelle de l'état"
            );
        }

        $this->setMessage("L'état des transactions a été modifié");
        $this->redirect("/admin/authorities/admin_authority_sae_statistiques.php?id=$authority_id");
    }
}
