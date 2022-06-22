<?php

class HeliosSAEController extends Controller {

	/**
	 * @throws RedirectException
	 */
	public function verificationAction(){
		$this->verifSuperAdmin();

		$transaction_id = $this->getRecuperateurPost()->get('transaction_id');

		try {
			$this->getObjectInstancier()->get(HeliosVerificationSAE::class)->verifArchiveThrow($transaction_id);
			$message = "La transaction a été traité par le SAE";
		} catch (Exception $e){
			$message =  $e->getMessage();
		}

		$this->setMessage($message);
		$this->redirect("/modules/helios/helios_transac_show.php?id=$transaction_id");
	}

	/**
	 * @throws RedirectException
	 */
	public function sendSAEAction(){
		$this->verifSuperAdmin();

		$transaction_id = $this->getRecuperateurPost()->get('transaction_id');

		try {
			$this->getObjectInstancier()->get(HeliosEnvoiSAE::class)->sendArchiveThrow($transaction_id);
			$message = "La transaction a été envoyé sur le SAE";
		} catch (Exception $e){
			$message =  $e->getMessage();
		}

		$this->setMessage($message);
		$this->redirect("/modules/helios/helios_transac_show.php?id=$transaction_id");
	}


	public function getActionPossible($status){
		$all_status = [
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
		return $all_status[$status]??[];
	}

	private function isActionPossible($statut_initial,$status_final){
		return in_array($status_final,$this->getActionPossible($statut_initial));
	}

	/**
	 * @throws RedirectException
	 */
	public function changeStatusAction(){
		$this->verifSuperAdmin();
		$transaction_id = $this->getRecuperateurPost()->get('transaction_id');
		$status_id = $this->getRecuperateurPost()->get('status_id');

		$heliosTransactionSQL = $this->getObjectInstancier()->get(HeliosTransactionsSQL::class);

		$status_info = $heliosTransactionSQL->getLastStatusInfo($transaction_id);

		if (! $status_info){
			$this->redirect("/","Cette transaction n'existe pas");
		}

		if ($this->isActionPossible($status_info['status_id'],$status_id)){
			$heliosTransactionSQL->updateStatus(
				$transaction_id,
				$status_id,
				"Modification manuelle de l'état"
			);
			$this->setMessage("Le status de la transaction a été modifiée");
		} else {
			$this->setErrorMessage("Impossible de changer le status de la transaction");
		}

		$this->redirect("/modules/helios/helios_transac_show.php?id=$transaction_id");
	}

	/**
	 * @throws RedirectException
	 */
	public function changeStatusBulkAction(){
		$this->verifSuperAdmin();
		$authority_id = $this->getRecuperateurPost()->get('authority_id');
		$status_id_from = $this->getRecuperateurPost()->get('status_id_from');
		$status_id_to = $this->getRecuperateurPost()->get('status_id_to');
		if (! $this->isActionPossible($status_id_from,$status_id_to)){
			$this->setErrorMessage("Action impossible");
			$this->redirect("/admin/authorities/admin_authority_sae_statistiques.php?id=$authority_id");
		}

		$heliosTransactionsSQL = $this->getObjectInstancier()->get(HeliosTransactionsSQL::class);
		$all = $heliosTransactionsSQL->getIdsByStatus(
			$status_id_from,
			$authority_id
		);

		foreach($all as $transaction_id){
			$heliosTransactionsSQL->updateStatus(
				$transaction_id,
				$status_id_to,
				"Modification manuelle de l'état"
			);
		}

		$this->setMessage("L'état des transactions a été modifié");
		$this->redirect("/admin/authorities/admin_authority_sae_statistiques.php?id=$authority_id");
	}

}