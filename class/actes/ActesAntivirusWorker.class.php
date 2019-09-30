<?php

class ActesAntivirusWorker implements IWorker {

	const QUEUE_NAME = 'actes-antivirus';

	private $actesTransactionSQL;
	private $actesRetriever;
	private $actesEnvelopeSQL;

	private $antivirus;

	private $logger;

	private $workerScript;

	public function __construct(
		ActesTransactionsSQL $actesTransactionSQL,
		ActesRetriever $actesRetriever,
		ActesEnvelopeSQL $actesEnvelopeSQL,
		Antivirus $antivirus,
		S2lowLogger $s2lowLogger,
		WorkerScript $workerScript
	){
		$this->actesTransactionSQL = $actesTransactionSQL;
		$this->actesRetriever = $actesRetriever;
		$this->actesEnvelopeSQL = $actesEnvelopeSQL;
		$this->antivirus = $antivirus;
		$this->logger = $s2lowLogger;
		$this->workerScript = $workerScript;
	}

	public function getQueueName() {
		return self::QUEUE_NAME;
	}

	public function getAllId(){
		return $this->actesTransactionSQL->getTransactionForAntiVirus();
	}

	public function getData($id){
		return $id;
	}

	public function getMutexName($data) {
		return sprintf("actes-transaction-%s",$data);
	}

	public function isDataValid($data) {
		$transaction_id = $data;
		$transaction_info = $this->actesTransactionSQL->getInfo($transaction_id);
		if ($transaction_info['antivirus_check']){
			$this->logger->notice("La transaction $transaction_id a déjà été analysé par l'antivirus");
			return false;
		}
		return true;
	}

	/**
	 * @param $data
	 * @return bool
	 * @throws Exception
	 */
	public function work($data){
		$transaction_id = $data;
		$this->logger->info("Traitement transaction $transaction_id");

		$transaction_info = $this->actesTransactionSQL->getInfo($transaction_id);
		if ($transaction_info['antivirus_check']){
			$this->logger->notice("La transaction $transaction_id a déjà été analysé par l'antivirus");
			return true;
		}

		$envelope_info = $this->actesEnvelopeSQL->getInfo($transaction_info["envelope_id"]);

		$archive_path = $this->actesRetriever->getPath($envelope_info['file_path']);
		if (! $this->antivirus->checkArchiveSanity($archive_path)){
			$message = $this->antivirus->getLastError();
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

		$this->workerScript->putJobByClassName(
			ActesAnalyseFichierAEnvoyerWorker::class,
			$transaction_info["envelope_id"]
		);
		return true;
	}
}