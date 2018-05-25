<?php

require_once SITEROOT."/public.ssl/modules/actes/class/ActesEnvelope.class.php";

class ActesAntivirus {

	private $actesTransactionSQL;
	private $actesRetriever;
	private $actesEnvelopeSQL;

	private $antivirus;
	private $errorMsg;

	private $logger;

	public function __construct(
		ActesTransactionsSQL $actesTransactionSQL,
		ActesRetriever $actesRetriever,
		ActesEnvelopeSQL $actesEnvelopeSQL,
		Antivirus $antivirus,
		Monolog\Logger $logger
	){
		$this->actesTransactionSQL = $actesTransactionSQL;
		$this->actesRetriever = $actesRetriever;
		$this->actesEnvelopeSQL = $actesEnvelopeSQL;
		$this->antivirus = $antivirus;
		$this->logger = $logger;
	}

	/**
	 * @param $transaction_id
	 * @return bool
	 * @throws Exception
	 */
	public function check($transaction_id){
		$this->logger->withName(self::class)->info("Traitement transaction $transaction_id");

		$transaction_info = $this->actesTransactionSQL->getInfo($transaction_id);
		$envelope_info = $this->actesEnvelopeSQL->getInfo($transaction_info["envelope_id"]);

		$archive_path = $this->actesRetriever->getPath($envelope_info['file_path']);
		if (! $this->antivirus->checkArchiveSanity($archive_path)){
			$message = $this->antivirus->errorMsg;
			$this->logger->withName(self::class)->notice(
				"Un virus a été trouvé pour la transaction $transaction_id",[$message]
			);
			$this->actesTransactionSQL->updateStatus($transaction_id, -1, $message);
			return false;
		}

		$this->actesTransactionSQL->setAntivirusCheck($transaction_id);
		$this->logger->withName(self::class)->info(
			"La transaction $transaction_id ne contient pas de virus"
		);
		return true;
	}
	
}