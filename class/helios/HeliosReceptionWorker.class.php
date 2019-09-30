<?php

class HeliosReceptionWorker implements IWorker,IWorkerAlwaysLaunch {


	const QUEUE_NAME = 'helios-reception-fichier';

	private $workerScript;
	private $s2lowLogger;

	public function __construct(
		S2lowLogger $s2lowLogger,
		WorkerScript $workerScript
	) {
		$this->s2lowLogger = $s2lowLogger;
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

		$ftp = new FTP();
		$ftp->setConnexionInfo(HELIOS_FTP_SERVER, HELIOS_FTP_PORT, HELIOS_FTP_LOGIN, HELIOS_FTP_PASSWORD,$this->workerScript);
		if (HELIOS_SENDING_MODE_DEMO){
			$ftp->setDeleteFileAfterDownload();
		}
		try {
			$this->s2lowLogger->info("Début de la récupération");
			/* Reception des fichiers*/
			$ftp->recupAll(HELIOS_FTP_RESPONSE_SERVER_PATH, HELIOS_FTP_RESPONSE_TMP_LOCAL_PATH);
			$this->s2lowLogger->info("Recuperation terminee");
		} catch (Exception $e){
			$this->s2lowLogger->info("Probleme lors de la recuperation des enveloppes : " . $e->getMessage() );
			exit;
		}

	}

	public function getMutexName($data) {
		return $this->getQueueName();
	}

	public function isDataValid($data) {
		return true;
	}

}