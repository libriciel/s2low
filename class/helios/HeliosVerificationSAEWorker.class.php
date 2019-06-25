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
		$date = date("Y-m-d",strtotime("-60 days"));
		return $this->heliosTransactionsSQL->getIdFromStatusWithSAE(9,$date);
	}

	/**
	 * @param $data
	 * @return void
	 * @throws Exception
	 */
	public function work($data){
		$this->heliosVerificationSAE->verifArchive($data);
	}
}