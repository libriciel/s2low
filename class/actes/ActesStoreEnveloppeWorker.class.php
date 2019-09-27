<?php

class ActesStoreEnveloppeWorker implements IWorker {

	const QUEUE_NAME = 'actes-store-enveloppe';

	public function getQueueName(){
		return self::QUEUE_NAME;
	}

	private $actesEnvelopeStorage;

	public function __construct(ActesEnvelopeStorage $actesEnvelopeStorage) {
		$this->actesEnvelopeStorage = $actesEnvelopeStorage;
	}

	public function getData($id){
		return $id;
	}

	public function getAllId(){
		return $this->actesEnvelopeStorage->getAllEnveloppeIdToStore();
	}

	/**
	 * @param $data
	 * @return void
	 * @throws Exception
	 */
	public function work($data){
		$this->actesEnvelopeStorage->storeNextFileById($data);
	}

	public function getMutexName($data) {
		return sprintf("%s-%s",self::QUEUE_NAME,$data);
	}

	public function isDataValid($data) {
		return true;
	}
}