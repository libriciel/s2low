<?php

class ActesSAEController extends Controller {

	/**
	 * @throws RedirectException
	 */
	public function sendAction(){
		$this->verifSuperAdmin();

		$transaction_id = $this->getRecuperateurPost()->get('transaction_id');

		try {
			$this->getObjectInstancier()->get(ActesArchiveControler::class)->sendArchiveThrow($transaction_id);
			$message = "La transaction a été envoyé au SAE";
		} catch (Exception $e){
			$message =  $e->getMessage();
		}

		$this->setMessage($message);
		$this->redirect("/modules/actes/actes_transac_show.php?id=$transaction_id");
	}

	/**
	 * @throws RedirectException
	 */
	public function verifAction(){
		$this->verifSuperAdmin();

		$transaction_id = $this->getRecuperateurPost()->get('transaction_id');

		try {
			$r = $this->getObjectInstancier()->get(ActesVerifSaeWorker::class)->verifArchiveThrow($transaction_id);
			if ($r){
				$message = "La transaction a été vérifié sur le SAE";
			} else {
				$message = "La transaction n'a pas encore été traité par le SAE";
			}

		} catch (Exception $e){
			$message =  $e->getMessage();
		}

		$this->setMessage($message);
		$this->redirect("/modules/actes/actes_transac_show.php?id=$transaction_id");
	}

}