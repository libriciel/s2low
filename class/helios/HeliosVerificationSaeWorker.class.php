<?php

class HeliosVerificationSaeWorker implements IWorker {

	const QUEUE_NAME = 'helios-verification-sae';

	private $heliosVerificationSAE;
	private $heliosTransactionsSQL;

	public function __construct(
		HeliosVerificationSAE $heliosVerificationSAE,
		HeliosTransactionsSQL $heliosTransactionsSQL
	) {
		$this->heliosVerificationSAE = $heliosVerificationSAE;
		$this->heliosTransactionsSQL = $heliosTransactionsSQL;
	}

	public function getQueueName(){
		return self::QUEUE_NAME;
	}

	public function getData($id){
		return $id;
	}

	public function getAllId(){
		return $this->heliosTransactionsSQL->getTransactionToPrepareToSAE(
			HeliosPrepareSaeWorker::NB_DAYS_ARCHIVE_AFTER,
			0,
			true,
			[HeliosStatusSQL::ENVOYER_AU_SAE]
		);
	}

	/**
	 * @param $data
	 * @return void
	 * @throws Exception
	 */
	public function work($data){
		$this->heliosVerificationSAE->verifArchive($data);
	}

	public function getMutexName($data) {
		return sprintf("%s-%s",self::QUEUE_NAME,$data);
	}

	public function isDataValid($data) {
		return true;
	}
}