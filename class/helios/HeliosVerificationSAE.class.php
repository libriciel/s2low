<?php

use Monolog\Logger;

class HeliosVerificationSAE {

	private $heliosTransactionsSQL;
	private $pastellWrapperFactory;
	private $logger;
	private $authoritySQL;
	private $pastellPropertiesSQL;

	public function __construct(
		PastellWrapperFactory $pastellWrapperFactory,
		Logger $logger,
		AuthoritySQL $authoritySQL,
		HeliosTransactionsSQL $heliosTransactionsSQL,
		PastellPropertiesSQL $pastellPropertiesSQL
	){
		$this->heliosTransactionsSQL = $heliosTransactionsSQL;
		$this->authoritySQL = $authoritySQL;
		$this->pastellWrapperFactory = $pastellWrapperFactory;
		$this->logger = $logger;
		$this->pastellPropertiesSQL = $pastellPropertiesSQL;
	}

	public function verifArchive($transaction_id){
		try {
			return $this->verifArchiveThrow($transaction_id);
		} catch (Exception $e){
			$this->logger->error("Problème lors de la vérification de l'archive : " . $e->getMessage());
			return false;
		}
	}

	/**
	 * @param $transactionInfo
	 * @return bool
	 * @throws Exception
	 */
	private function verifArchiveThrow($transaction_id){

		$transactionInfo = $this->heliosTransactionsSQL->getInfo($transaction_id);

		$this->logger->info("Vérification de la transaction {$transactionInfo['id']} ");

		$this->authoritySQL->verifHasPastell($transactionInfo[HeliosTransactionsSQL::AUTHORITY_ID]);

		$pastellProperties = $this->pastellPropertiesSQL->getPastellProperties(
			$transactionInfo[HeliosTransactionsSQL::AUTHORITY_ID]
		);

		$pastellWrapper = $this->pastellWrapperFactory->getNewInstance($pastellProperties);

		$sae_transfert_identifier = $transactionInfo['sae_transfer_identifier'];

		$info = $pastellWrapper->getInfo($sae_transfert_identifier);
		if(!$info){
			throw new RecoverableException($pastellWrapper->getLastError());
		}
		try {
			$reply_sae = $pastellWrapper->getFile($sae_transfert_identifier, 'reply_sae');
		} catch (Exception $e){
			throw new RecoverableException("Pas encore de réponse (".$e->getMessage().")");
		}

		@ $xml = simplexml_load_string($reply_sae);

		if (! $xml){
			throw new RecoverableException( "Impossible de lire le fichier reply.xml : $reply_sae");
		}


		$nodeName = strval($xml->getName());
		$xml_message = utf8_decode(strval($xml->{'ReplyCode'}) . " - " . strval($xml->{'Comment'}));

		if ($nodeName == 'ArchiveTransferAcceptance' ||
			($nodeName == 'ArchiveTransferReply' && (strval($xml->{'ReplyCode'}) == '000'))){
			$url = $info['data']['url_archive'];
			$msg = "La transaction {$transactionInfo['id']} a été acceptée par le SAE : \n$xml_message";
			$this->heliosTransactionsSQL->updateStatus($transactionInfo['id'],10,$msg);
			$this->heliosTransactionsSQL->setArchiveURL($transactionInfo['id'],$url);
		} else {
			$msg = "La transaction {$transactionInfo['id']} a été refusé par le SAE: \n$xml_message";
			$this->heliosTransactionsSQL->updateStatus($transactionInfo['id'],11,$msg);
		}

		$this->logger->info("$msg");

		$pastellWrapper->delete($sae_transfert_identifier);
		$this->logger->info("Document $sae_transfert_identifier supprimé sur Pastell");

		return true;
	}


}