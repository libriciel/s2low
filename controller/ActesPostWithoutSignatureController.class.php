<?php

class ActesPostWithoutSignatureController extends Controller {

	/**
	 * @throws RedirectException
	 */
	public function postAction(){
		$this->verifUser();
		$transaction_id = $this->getRecuperateurPost()->getInt('id');
		$actesTransactionSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
		$transaction_info = $actesTransactionSQL->getInfo($transaction_id);
		if (! $transaction_info){
			$this->redirect(
				"/modules/actes/",
				"Aucun identifiant de transaction trouvé"
			);
		}

		$serviceUser = $this->getObjectInstancier()->get(ServiceUser::class);

		if ($this->me->getId() != $transaction_info['user_id'] && ! $serviceUser->areCollegues($this->me->getId(),$transaction_info['user_id'])){
			$this->redirect(
				"/modules/actes/actes_transac_show.php?id=$transaction_id",
				"Vous n'avez pas le droit de faire cela"
			);
		}

		$message = "La transaction $transaction_id a été posté sans signature";
		$workerClassName = ActesAnalyseFichierAEnvoyerWorker::class;


		$actesTransactionSQL->updateStatus($transaction_id,ActesStatusSQL::STATUS_POSTE,$message);

		$workerScript = $this->getObjectInstancier()->get(WorkerScript::class);
		$workerScript->putJobByClassName($workerClassName,$transaction_info['envelope_id']);

		$this->redirect("/modules/actes/actes_transac_show.php?id=$transaction_id",$message);
	}

}