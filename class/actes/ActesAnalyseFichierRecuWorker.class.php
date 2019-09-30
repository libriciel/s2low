<?php

class ActesAnalyseFichierRecuWorker implements IWorker {


	const QUEUE_NAME = 'actes-analyse-fichier-recu';


	private $actesAnalyseFichierRecuController;

	public function __construct(
		ActesAnalyseFichierRecuController $actesAnalyseFichierRecuController
	) {
		$this->actesAnalyseFichierRecuController = $actesAnalyseFichierRecuController;
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
		return $this->actesAnalyseFichierRecuController->getAllDirectory();
	}

	/**
	 * @param $data
	 * @return void
	 * @throws Exception
	 */
	public function work($data){
		$this->actesAnalyseFichierRecuController->analyseOneFileMoveIfError($data);
	}

	public function getMutexName($data) {
		return sprintf("%s-%s",self::QUEUE_NAME,$data);
	}

	public function isDataValid($data) {
		return true;
	}

}