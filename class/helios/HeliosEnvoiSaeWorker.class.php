<?php

class HeliosEnvoiSaeWorker implements IWorker {

	const MAX_TRANSACTION_TO_SEND = 100;

	const QUEUE_NAME = 'helios-envoi-sae';

	private $heliosArchiveControler;
	private $heliosTransactionsSQL;

	public function __construct(
		HeliosEnvoiSAE $heliosArchiveControler,
		HeliosTransactionsSQL $heliosTransactionsSQL
	) {
		$this->heliosArchiveControler = $heliosArchiveControler;
		$this->heliosTransactionsSQL = $heliosTransactionsSQL;
	}

	public function getQueueName(){
		return self::QUEUE_NAME;
	}

	public function getData($id){
		return $id;
	}

	/**
	 * On envoie que les 100 premiers id car sinon, il est possible que le script de récup sur le cloud plante (suite à l'expiration du ticket)
	 *
	 * @return 0|array|int[]
	 */
	public function getAllId(){
		return array_slice(
			$this->heliosTransactionsSQL->getTransactionToPrepareToSAE(
			HeliosPrepareSaeWorker::NB_DAYS_ARCHIVE_AFTER,
			0,
			true,
			[HeliosStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE]
			),
			0,
			self::MAX_TRANSACTION_TO_SEND
			);
	}

	/**
	 * @param $data
	 * @return void
	 * @throws Exception
	 */
	public function work($data){
		$this->heliosArchiveControler->sendArchive($data);
	}

	public function getMutexName($data) {
		return sprintf("%s-%s",self::QUEUE_NAME,$data);
	}

	public function isDataValid($data) {
		return true;
	}
}