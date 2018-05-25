<?php

class WorkerScript {

	const QUEUE_NAME = 'actes-antivirus';
	const QUEUE_DELAY_RETRY_IN_SECONDS = 60;
	const MIN_EXECUTION_TIME_IN_SECONDS = 10; //uniquement pour le mode non beanstalked

	private $actesTransactionSQL;
	private $actesRetriever;
	private $actesEnvelopeSQL;

	private $antivirus;
	private $errorMsg;

	private $logger;
	private $beanstalkdWrapper;

	public function __construct(
		ActesTransactionsSQL $actesTransactionSQL,
		ActesRetriever $actesRetriever,
		ActesEnvelopeSQL $actesEnvelopeSQL,
		Antivirus $antivirus,
		BeanstalkdWrapper $beanstalkdWrapper,
		S2lowLogger $s2lowLogger
	){
		$this->actesTransactionSQL = $actesTransactionSQL;
		$this->actesRetriever = $actesRetriever;
		$this->actesEnvelopeSQL = $actesEnvelopeSQL;
		$this->antivirus = $antivirus;
		$this->logger = $s2lowLogger;
		$this->beanstalkdWrapper = $beanstalkdWrapper;
	}

	public function putJob($data){
		$this->beanstalkdWrapper->put(self::QUEUE_NAME,$data);
	}

	public function script(){
		$this->logger->enableStdOut();
		if ($this->beanstalkdWrapper->isModeBeanstalked()){
			$this->beanstalkdWorker();
		} else {
			$this->oldSchoolScript();
		}
	}

	public function rebuildQueue(){
		$this->logger->enableStdOut();

		$this->beanstalkdWrapper->emptyQueue(self::QUEUE_NAME);
		$this->logger->info("Reconstruction de la file ".self::QUEUE_NAME);
		foreach($this->getAll() as $id){
			$this->putJob($id);
			$this->logger->info("Ajout de la transaction $id dans la file d'attente");
		}
		$this->logger->info("Reconstruction de la file ".self::QUEUE_NAME.": OK");
	}

	private function beanstalkdWorker(){
		$queue = $this->beanstalkdWrapper->getQueue(self::QUEUE_NAME);

		while($job = $queue->reserve()){
			$transaction_id = "undefined";
			try {
				$transaction_id = $job->getData();
				$this->check($transaction_id);
				$queue->delete($job);
			} catch (Exception $e){
				$this->logger->error(
					"Problème lors du check de l'antivirus",
					['transaction_id'=>$transaction_id,'error_message'=>$e->getMessage()]
				);
				$queue->release(
					$job,
					\Pheanstalk\PheanstalkInterface::DEFAULT_PRIORITY,
					self::QUEUE_DELAY_RETRY_IN_SECONDS
				);
			}
		}
		return true;
	}


	private function oldSchoolScript(){
		$start = time();

		$this->logger->info("Démarrage en mode supervisord");

		try {
			$this->checkAll();
		} catch (Exception $e){
			$this->logger->critical("Erreur lors de l'execution du script",[$e]);
		}

		$sleep = self::MIN_EXECUTION_TIME_IN_SECONDS - (time() -$start);
		if ($sleep > 0){
			$this->logger->debug("Arret du script $sleep secondes");
			sleep($sleep);
		}
	}

	/**
	 * @throws Exception
	 */
	public function checkAll(){
		$sigTermHandler = new SigTermHandler();

		$id_list = $this->getAll();
		$this->logger->info(count($id_list) ." actes trouvés");

		foreach($id_list as $id){
			if ($sigTermHandler->isSigtermCalled()){
				$this->logger->notice("SIGTERM reçu !");
				exit;
			}
			$this->check($id);
		}
	}

	private function getAll(){
		return $this->actesTransactionSQL->getTransactionForAntiVirus();
	}

	/**
	 * @param $transaction_id
	 * @return bool
	 * @throws Exception
	 */
	public function check($transaction_id){
		$this->logger->info("Traitement transaction $transaction_id");

		$transaction_info = $this->actesTransactionSQL->getInfo($transaction_id);
		$envelope_info = $this->actesEnvelopeSQL->getInfo($transaction_info["envelope_id"]);

		$archive_path = $this->actesRetriever->getPath($envelope_info['file_path']);
		if (! $this->antivirus->checkArchiveSanity($archive_path)){
			$message = $this->antivirus->errorMsg;
			$this->logger->notice(
				"Un virus a été trouvé pour la transaction $transaction_id",[$message]
			);
			$this->actesTransactionSQL->updateStatus($transaction_id, -1, $message);
			return false;
		}

		$this->actesTransactionSQL->setAntivirusCheck($transaction_id);
		$this->logger->info(
			"La transaction $transaction_id ne contient pas de virus"
		);
		return true;
	}



}