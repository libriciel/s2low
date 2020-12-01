<?php

class HeliosReceptionWorker implements IWorker {


	const QUEUE_NAME = 'helios-reception-fichier';

	private $workerScript;
	private $s2lowLogger;
	/** @var FTPHeliosReceiver  */
    private $ftpFileGetter;

    public function __construct(
        S2lowLogger $s2lowLogger,
        WorkerScript $workerScript,
        FTPHeliosReceiver $FTPHeliosReceiver
	) {
		$this->s2lowLogger = $s2lowLogger;
		$this->workerScript = $workerScript;
		$this->ftpFileGetter = $FTPHeliosReceiver;
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
        $sigtermHandler = SigTermHandler::getInstance();
		try {
			$this->s2lowLogger->info("Début de la récupération");
            $this->ftpFileGetter->retrieveNames();
            /* Reception des fichiers*/
            foreach ($this->ftpFileGetter as $file){
                if ($this->workerScript){
                    $this->workerScript->putJobByClassName(HeliosAnalyseFichierRecuWorker::class,$file);
                }
                if ($sigtermHandler->isSigtermCalled()){
                    $this->ftpFileGetter->finTraitement();
                }
            }
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