<?php

use \Monolog\Logger;

class HeliosPrepareEnvoiSAE {

	private $lastError;

	private $heliosTransactionsSQL;
	private $pesAllerRetriever;
	private $userSQL;
	private $authoritySQL;
	private $logger;

	public function __construct(
		PesAllerRetriever $pesAllerRetriever,
		Logger $logger,
		UserSQL $userSQL,
		AuthoritySQL $authoritySQL,
		HeliosTransactionsSQL $heliosTransactionsSQL
	){

		$this->heliosTransactionsSQL = $heliosTransactionsSQL;
		$this->authoritySQL = $authoritySQL;
		$this->pesAllerRetriever = $pesAllerRetriever;
		$this->logger = $logger;
		$this->userSQL = $userSQL;
	}

	public function getLastError(){
		return $this->lastError;
	}

	public function setArchiveEnAttenteEnvoiSEA(int $user_id,int $transaction_id) : bool {
		try {
			$transaction_info = $this->getTransactionInfo($transaction_id);

			$this->isAllowToSendArchive($user_id,$transaction_info);

			if (!in_array($transaction_info['last_status_id'], array(8,4, 6, 11,20))) {
				throw new UnrecoverableException(
					"Impossible d'archiver une transaction qui n'est pas en état « Information disponible », « acquitté » ou « refusé »."
				);
			}
			$this->authoritySQL->verifHasPastell($transaction_info[HeliosTransactionsSQL::AUTHORITY_ID]);
		} catch (Exception $e){
			$this->logger->error($e->getMessage());
			$this->lastError = $e->getMessage();
			return false;
		}

		$this->heliosTransactionsSQL->updateStatus(
			$transaction_id,
			HeliosStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE,
			"En attente de l'envoi au SAE"
		);
		$this->logger->info("La transaction $transaction_id passe en attente de transmission au SAE");
		return true;
	}

	/**
	 * @param $transaction_id
	 * @return array
	 * @throws UnrecoverableException
	 */
	private function getTransactionInfo($transaction_id) : array{
		$transaction_info = $this->heliosTransactionsSQL->getInfo($transaction_id);
		if (!$transaction_info){
			throw new UnrecoverableException("La transaction $transaction_id n'existe pas");
		}
		return $transaction_info;
	}

	/**
	 * @param $user_id
	 * @param $transactionsInfo
	 * @return bool
	 * @throws Exception
	 */
	private function isAllowToSendArchive($user_id,$transactionsInfo){
		if ($transactionsInfo['user_id'] == $user_id){
			return true;
		}
		$user_info = $this->userSQL->getInfo($user_id);

		if ($user_info['role'] == 'SADM'){
			return true;
		}
		if ($user_info['role'] != 'ADM'){
			throw new UnrecoverableException("Accès interdit");
		}
		if ($user_info[UserSQL::AUTHORITY_ID] == $transactionsInfo[HeliosTransactionsSQL::AUTHORITY_ID]){
			return true;
		}

		throw new UnrecoverableException("Accès interdit");
	}

}