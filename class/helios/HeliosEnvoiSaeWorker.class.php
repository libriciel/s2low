<?php

class HeliosEnvoiSaeWorker implements IWorker {

	const QUEUE_NAME = 'helios-envoi-sae';

	private $heliosArchiveControler;
	private $heliosTransactionSQL;

	public function __construct(
		HeliosEnvoiSAE $heliosArchiveControler,
		HeliosTransactionsSQL $heliosTransactionsSQL
	) {
		$this->heliosArchiveControler = $heliosArchiveControler;
		$this->heliosTransactionSQL = $heliosTransactionsSQL;
	}

	public function getQueueName(){
		return self::QUEUE_NAME;
	}

	public function getData($id){
		return $id;
	}

	public function getAllId(){
		return $this->heliosTransactionSQL->getIdsByStatus(
			HeliosStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE
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

}