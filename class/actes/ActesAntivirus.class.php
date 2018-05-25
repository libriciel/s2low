<?php

require_once SITEROOT."/public.ssl/modules/actes/class/ActesEnvelope.class.php";

class ActesAntivirus {

	private $actesTransactionSQL;
	private $actesRetriever;

	public function __construct(ActesTransactionsSQL $actesTransactionSQL,ActesRetriever $actesRetriever){
		$this->actesTransactionSQL = $actesTransactionSQL;
		$this->actesRetriever = $actesRetriever;
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

		$archive_path = $this->actesRetriever->getPath($zeEnv->get('file_path'));

		if ($zeEnv->checkArchiveSanity($archive_path)){

			$this->actesTransactionSQL->setAntivirusCheck($transaction_id);

			echo "OK";

		} else {
			$message = $zeEnv->getErrorMsg();
			echo "Virus Found : $message";
			$this->actesTransactionSQL->updateStatus($transaction_id, -1, $message);
		}

		echo "\n";
	}

}