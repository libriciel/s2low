<?php

use Monolog\Logger;

class HeliosEnvoiSAE {

	private $heliosTransactionsSQL;
	private $pastellWrapperFactory;
	private $pesAllerRetriever;
	private $logger;
	private $authoritySQL;
	private $pastellPropertiesSQL;
	private $pesAllerStorage;

	public function __construct(
        PesAllerRetriever $pesAllerRetriever,
		PastellWrapperFactory $pastellWrapperFactory,
		Logger $logger,
		AuthoritySQL $authoritySQL,
		HeliosTransactionsSQL $heliosTransactionsSQL,
		PastellPropertiesSQL $pastellPropertiesSQL,
		PesAllerStorage $pesAllerStorage
    ){
		$this->heliosTransactionsSQL = $heliosTransactionsSQL;
		$this->authoritySQL = $authoritySQL;
		$this->pastellWrapperFactory = $pastellWrapperFactory;
		$this->pesAllerRetriever = $pesAllerRetriever;
		$this->logger = $logger;
		$this->pastellPropertiesSQL = $pastellPropertiesSQL;
		$this->pesAllerStorage = $pesAllerStorage;
	}

	public function sendAllArchive($authority_id = 0){
		$sigtermHandler = SigTermHandler::getInstance();
		$this->logger->info("Début de l'envoi");
		$info_list = $this->heliosTransactionsSQL->getIdsByStatus(
			HeliosStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE,
			$authority_id
		);
		$this->logger->info( count($info_list)." transactions à envoyer...");
		foreach($info_list as $transaction_id){
			$this->logger->info("Envoi de la transaction $transaction_id.");
			$this->sendArchive($transaction_id);
            if ($sigtermHandler->isSigtermCalled()){
                break;
            }
		}
		$this->logger->info( "Fin de l'envoi");
	}

	public function sendArchive($id){
		try {
			$this->sendArchiveThrow($id);
			$this->logger->info("La transaction $id a été envoyé à Pastell");
		} catch (Exception $e){
			$message = "Le document n'a pas pu être envoyé sur Pastell : " . $e->getMessage();
			$this->heliosTransactionsSQL->updateStatus($id,
				HeliosStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE,
				$message);
			$this->logger->error($message);
			return false;
		}
		return true;
	}

	/**
	 * @param int $transaction_id
	 * @return bool
	 * @throws Exception
	 */
	public function sendArchiveThrow(int $transaction_id) : bool {
		try {
			$transactionsInfo = $this->heliosTransactionsSQL->getInfo($transaction_id);

			$this->authoritySQL->verifHasPastell($transactionsInfo[HeliosTransactionsSQL::AUTHORITY_ID]);

			$pes_aller_filepath = $this->pesAllerRetriever->getPath($transactionsInfo['sha1']);

			if (! $pes_aller_filepath){
				throw new RecoverableException("Impossible de récupérer le PES ALLER {$transactionsInfo['sha1']}");
			}

			$pes_acquit_filepath = HELIOS_RESPONSES_ROOT . "/" . $transactionsInfo['acquit_filename'];

			$pastellProperties = $this->pastellPropertiesSQL->getPastellProperties($transactionsInfo[HeliosTransactionsSQL::AUTHORITY_ID]);

			$pastell = $this->pastellWrapperFactory->getNewInstance($pastellProperties);

			$id_d = $pastell->createHelios($transactionsInfo);

			if (!$id_d) {
				throw new UnrecoverableException($pastell->getLastError());
			}

			$pastell->postFile($id_d, 'fichier_pes', $pes_aller_filepath, $transactionsInfo['complete_name']);
			$pastell->postFile($id_d, 'fichier_reponse', $pes_acquit_filepath, $transactionsInfo['acquit_filename']);

			$result = $pastell->sendSAE($id_d, $pastellProperties->helios_action);

			if (!$result) {
				throw new UnrecoverableException(
					"Impossible d'envoyer la transaction sur as@lae : " . $pastell->getLastError()
				);
			}
			$this->heliosTransactionsSQL->updateStatus(
				$transaction_id,
				HeliosStatusSQL::ENVOYER_AU_SAE,
				"Envoie de la transaction $transaction_id à Pastell"
			);
			$this->heliosTransactionsSQL->setSAETransferIdentifier($transaction_id, $id_d);
		} catch (Exception $e) {
			if (! empty($id_d)){
				try {
					$pastell->delete($id_d);
				} catch (Exception $e){
					/** Nothing to do */
				}
			}
			throw $e;
		}


		$this->pesAllerStorage->deleteIfIsInCloud($transactionsInfo['sha1']);


		return true;
	}

}