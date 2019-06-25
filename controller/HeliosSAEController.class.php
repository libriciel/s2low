<?php

class HeliosSAEController extends Controller {

	/**
	 * @throws RedirectException
	 */
	public function verificationAction(){
		$transaction_id = $this->getRecuperateurPost()->get('transaction_id');

		try {
			$this->getObjectInstancier()->get(HeliosVerificationSAE::class)->verifArchiveThrow($transaction_id);
			$message = "La transaction a été accepté par le SAE";
		} catch (Exception $e){
			$message =  $e->getMessage();
		}

		$this->setMessage($message);
		$this->redirect("/modules/helios/helios_transac_show.php?id=$transaction_id");
	}
}