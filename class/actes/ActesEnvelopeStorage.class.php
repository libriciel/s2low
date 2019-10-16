<?php

use Symfony\Component\Filesystem\Filesystem;

class ActesEnvelopeStorage {

	const CONTAINER_NAME = 'acte_envelope';

	private $actes_files_upload_root;
	private $actesEnvelopeSQL;
	private $openStackSwiftWrapper;
	private $logger;


	public function __construct(
		$actes_files_upload_root,
		ActesEnvelopeSQL $actesEnvelopeSQL,
		OpenStackSwiftWrapper $openStackSwiftWrapper,
		Monolog\Logger $logger
	) {
		$this->actes_files_upload_root = $actes_files_upload_root;
		$this->actesEnvelopeSQL = $actesEnvelopeSQL;
		$this->openStackSwiftWrapper = $openStackSwiftWrapper;
		$this->logger = $logger;
	}

	public function getAllEnveloppeIdToStore(){
		return $this->actesEnvelopeSQL->getAllEnvelopepIdToSendInCloud();
	}
	
	/**
	 * @param $transaction_id
	 * @return bool
	 * @throws Exception
	 */
	public function storeNextFileById($envelope_id){
		$envelope_info = $this->actesEnvelopeSQL->getInfo($envelope_id);
		$this->logger->debug(
			"Storing envelope {$envelope_info['id']} - ".
			"file {$envelope_info['file_path']}"
		);
		if (! $envelope_info['file_path'] ){
			$this->logger->error(
				"Unable to store envelope #{$envelope_info['id']} in cloud : file_path not set ! ",
				$envelope_info
			);
			$this->actesEnvelopeSQL->setEnveloppeNotAvailable($envelope_info['id']);
			return false;
		}
		if ( ! file_exists($this->actes_files_upload_root."/".$envelope_info['file_path'])){
			$this->logger->error(
				"Unable to store {$envelope_info['file_path']} in cloud : file did not exist ! ",
				$envelope_info
			);
			$this->actesEnvelopeSQL->setEnveloppeNotAvailable($envelope_info['id']);
			return false;
		}
		$this->logger->info("Storing file ".$this->actes_files_upload_root."/".$envelope_info['file_path']);

		$this->openStackSwiftWrapper->sendFile(
			self::CONTAINER_NAME,
			$this->actes_files_upload_root."/".$envelope_info['file_path'],
			$envelope_info['file_path']
		);

		$this->actesEnvelopeSQL->setTransactionInCloud($envelope_info['id']);
		$this->logger->info("Stored file : {$envelope_info['file_path']}");
		return true;
	}

	public function deleteIfIsInCloud($actes_envelope_file_path){
		try {
			$file = $this->actes_files_upload_root . "/" . $actes_envelope_file_path;
			if (!$this->openStackSwiftWrapper->fileExistsOnCloud(
				self::CONTAINER_NAME,
				$actes_envelope_file_path
			)) {
				$this->logger->info("Actes $actes_envelope_file_path not existing on cloud : not deleted");
				return false;
			}
			$this->logger->info("Deleting Actes : $actes_envelope_file_path");

			$filesystem = new Filesystem();
			$filesystem->remove($file);
			return true;
		}catch (Exception $e){
			$this->logger->alert(
				"Problème lors de la supression de l'acte $actes_envelope_file_path : " . $e->getMessage()
			);
			return false;
		}
	}
	/**
	 * @param $min_date
	 * @param $max_date
	 * @param bool $confirm
	 */
	public function grandMenage($min_date,$max_date,$confirm){

		$this->logger->info("Deleting files beetwen $min_date and $max_date");

		$sqlQuery = $this->actesEnvelopeSQL->getOlderTransactionHandle($min_date,$max_date);

		$sigtermHandler = SigTermHandler::getInstance();
		while($sqlQuery->hasMoreResult()){
			$actes_envelope = $sqlQuery->fetch();

			$this->logger->debug("Analysing file : {$actes_envelope['file_path']} {$actes_envelope['id']} - {$actes_envelope['submission_date']}");
			$filename = $this->actes_files_upload_root."/{$actes_envelope['file_path']}";
			if (! file_exists($filename)){
				$this->logger->debug( "File not exists {$actes_envelope['file_path']} [PASS]");
				continue;
			}
			if ($this->openStackSwiftWrapper->fileExistsOnCloud(
				ActesEnvelopeStorage::CONTAINER_NAME,
				$actes_envelope['file_path'])
			){
				$this->logger->info( "File {$actes_envelope['file_path']} exists on cloud : deleting on file system");
				if($confirm){
					unlink($filename);
					$this->logger->info( "File {$actes_envelope['file_path']} deleted");
				} else {
					$this->logger->debug( "File {$actes_envelope['file_path']} will be deleted if confirm is ok");
				}
			}
			if ($sigtermHandler->isSigtermCalled()){
				break;
			}

		}
	}

}