<?php

class HeliosAnalyseFichierAEnvoyerWorker implements IWorker {

	const QUEUE_NAME = 'helios-analyse-fichier-a-envoyer';


	private $heliosEnvoiControler;
	private $heliosTransactionsSQL;

	public function __construct(
		HeliosEnvoiControler $heliosEnvoiControler,
		HeliosTransactionsSQL $heliosTransactionsSQL
	) {
		$this->heliosEnvoiControler = $heliosEnvoiControler;
		$this->heliosTransactionsSQL = $heliosTransactionsSQL;
	}

	public function getQueueName(){
		return self::QUEUE_NAME;
	}

	public function getData($id){
		return $id;
	}

	/**
	 * @return array|false|int[]
	 * @throws Exception
	 */
	public function getAllId(){
		return $this->heliosTransactionsSQL->getIdsByStatus(HeliosTransactionsSQL::POSTE);
	}

	/**
	 * @param $data
	 * @return void
	 * @throws Exception
	 */
	public function work($data){
		$this->heliosEnvoiControler->validateOneTransaction($data);
	}

	public function getMutexName($data) {
		return sprintf("%s-%s",self::QUEUE_NAME,$data);
	}

	public function isDataValid($data) {
		$status_id = $this->heliosTransactionsSQL->getLatestStatusId($data);
		return $status_id == HeliosStatusSQL::POSTE;
	}


}