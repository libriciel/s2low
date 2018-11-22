<?php

class ActesPrepareSaeWorker implements IWorker {

	const QUEUE_NAME = 'actes-prepare-sae';

	const NB_DAYS_ARCHIVE_AFTER = 62;

	private $actesTransactionsSQL;
	private $s2lowLogger;
	private $actesArchiveControler;

	public function __construct(
		ActesTransactionsSQL $actesTransactionsSQL,
		S2lowLogger $s2lowLogger,
		ActesArchiveControler $actesArchiveControler
	) {
		$this->actesTransactionsSQL = $actesTransactionsSQL;
		$this->s2lowLogger = $s2lowLogger;
		$this->actesArchiveControler = $actesArchiveControler;
	}

	public function getQueueName(){
		return self::QUEUE_NAME;
	}

	public function getData($id){
		return $id;
	}

	public function getAllId(){
		return $this->actesTransactionsSQL->getTransactionToArchive(self::NB_DAYS_ARCHIVE_AFTER);
	}

	/**
	 * @param $transaction_id
	 * @return void
	 */
	public function work($transaction_id){
		$transaction_info = $this->actesTransactionsSQL->getInfo($transaction_id);
		$this->actesArchiveControler->setArchiveEnAttenteEnvoiSEA(
			$transaction_info['user_id'],
			$transaction_id
		);
	}
}