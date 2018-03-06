<?php

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

	/**
	 * @throws Exception
	 */
	public function storeAll(){
		$result = $this->actesEnvelopeSQL->getAllTransactionToSendInCloud();
		$sigtermHandler = new SigTermHandler();
		foreach($result as $transaction_info){
			$this->storeNextFile($transaction_info);
			if ($sigtermHandler->isSigtermCalled()){
				break;
			}
		}
	}

	/**
	 * @param $transaction_info
	 * @return bool
	 * @throws Exception
	 */
	public function storeNextFile($transaction_info){
		$this->logger->debug(
			"Storing envelope {$transaction_info['id']} - ".
			"file {$transaction_info['file_path']}"
		);
		if ( ! file_exists($this->actes_files_upload_root."/".$transaction_info['file_path'])){
			$this->logger->error(
				"Unable to store {$transaction_info['file_path']} in cloud : file did not exist ! ",
				$transaction_info
			);
			return false;
		}
		$this->logger->info("Storing file ".$this->actes_files_upload_root."/".$transaction_info['file_path']);

		$this->openStackSwiftWrapper->sendFile(
			self::CONTAINER_NAME,
			$this->actes_files_upload_root."/".$transaction_info['file_path'],
			$transaction_info['file_path']
		);

		$this->actesEnvelopeSQL->setTransactionInCloud($transaction_info['id']);
		$this->logger->info("Stored file : {$transaction_info['file_path']}");
		return true;
	}


	/**
	 * @param $min_date
	 * @param $max_date
	 * @param bool $confirm
	 */
	public function grandMenage($min_date,$max_date,$confirm){

		$this->logger->info("Deleting files beetwen $min_date and $max_date");

		$sqlQuery = $this->actesEnvelopeSQL->getOlderTransactionHandle($min_date,$max_date);

		$sigtermHandler = new SigTermHandler();
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