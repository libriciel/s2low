<?php

class ActesReceptionFichierWorker implements IWorker {


	const QUEUE_NAME = 'actes-reception-fichier';

	private $actesImapRetrieve;
	private $workerScript;

	public function __construct(
		ActesImapRetrieve $actesImapRetrieve,
		WorkerScript $workerScript
	) {
		$this->actesImapRetrieve = $actesImapRetrieve;
		$this->workerScript = $workerScript;
	}

	public function getQueueName(){
		return self::QUEUE_NAME;
	}

	public function getData($id){
		return $id;
	}

	public function getAllId(){
		return [1];
	}

	/**
	 * @param $data
	 * @return void
	 * @throws Exception
	 */
	public function work($data){
		$this->actesImapRetrieve->retrieve();
	}

	public function getMutexName($data) {
		return $this->getQueueName();
	}

	public function isDataValid($data) {
		return true;
	}

}