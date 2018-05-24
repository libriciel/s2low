<?php

class ActesAntivirusController {


	private $actesTransactionSQL;

	public function __construct(ActesTransactionsSQL $actesTransactionSQL){
		$this->actesTransactionSQL = $actesTransactionSQL;
	}

	/**
	 * @param $transaction_id
	 * @throws Exception
	 */
	public function check($transaction_id){

		echo "Traitement transaction $transaction_id : ";
		$zeTrans = new ActesTransaction();
		$zeTrans->setId($transaction_id);
		$zeTrans->init();

		$zeEnv = new ActesEnvelope($zeTrans->get("envelope_id"));
		$zeEnv->init();

		$archive_path =  ACTES_FILES_UPLOAD_ROOT."/". $zeEnv->get('file_path');

		if ($zeEnv->checkArchiveSanity($archive_path)){

			$this->actesTransactionSQL->setAntivirusCheck($$transaction_id);

			echo "OK";

		} else {
			$message = $zeEnv->getErrorMsg();
			echo "Virus Found : $message";
			$this->actesTransactionSQL->updateStatus($$transaction_id, -1, $message);
		}

		echo "\n";
	}

}