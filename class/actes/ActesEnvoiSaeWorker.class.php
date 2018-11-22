<?php

class ActesEnvoiSaeWorker implements IWorker {

	const QUEUE_NAME = 'actes-envoi-sae';

	private $actesArchiveControler;

	public function __construct(
		ActesArchiveControler $actesArchiveControler
	) {
		$this->actesArchiveControler = $actesArchiveControler;
	}

	public function getQueueName(){
		return self::QUEUE_NAME;
	}

	public function getData($id){
		return $id;
	}

	public function getAllId(){
		return $this->actesArchiveControler->getAllTransactionIdToSend();
	}

	/**
	 * @param $data
	 * @return void
	 * @throws Exception
	 */
	public function work($data){
		$this->actesArchiveControler->sendArchive($data);
	}

}