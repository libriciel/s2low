<?php

use \Symfony\Component\Filesystem\Filesystem;

class PesAllerStorage {

    const CONTAINER_NAME = "pes_aller";

    private $helios_files_upload_root;
    private $heliosTransactionsSQL;
    private $openStackSwiftWrapper;
    private $logger;


    public function __construct(
        $helios_files_upload_root,
        HeliosTransactionsSQL $heliosTransactionsSQL,
        OpenStackSwiftWrapper $openStackSwiftWrapper,
        Monolog\Logger $logger
    ) {
        $this->helios_files_upload_root = $helios_files_upload_root;
        $this->heliosTransactionsSQL = $heliosTransactionsSQL;
        $this->openStackSwiftWrapper = $openStackSwiftWrapper;
        $this->logger = $logger;
    }

	/**
	 * @deprecated
	 * @throws Exception
	 */
    public function storeAll(){
        $result = $this->heliosTransactionsSQL->getAllTransactionToSendInCloud();
		$sigtermHandler = SigTermHandler::getInstance();
        foreach($result as $transaction_info){
            $this->storeNextFile($transaction_info);
            if ($sigtermHandler->isSigtermCalled()){
                break;
            }
        }
    }

    public function getAllTransactionIdToStore(){
    	return $this->heliosTransactionsSQL->getAllTransactionIdToSendInCloud();
	}


	/**
	 * @param $transaction_id
	 * @return bool
	 * @throws Exception
	 */
	public function storeNextFileById($transaction_id){
    	$transaction_info = $this->heliosTransactionsSQL->getInfo($transaction_id);
    	return $this->storeNextFile($transaction_info);
	}

	/**
	 * @param $transaction_info
	 * @return bool
	 * @throws Exception
	 */
    public function storeNextFile($transaction_info){
        $this->logger->info(
            "Storing transaction {$transaction_info['id']} - ".
            "file {$transaction_info['filename']} - ".
            "{$transaction_info['sha1']}"
        );
        if ( ! file_exists($this->helios_files_upload_root."/".$transaction_info['sha1'])){
            return true;
        }
        $this->logger->info("Storing file ".$this->helios_files_upload_root."/".$transaction_info['sha1']);

        $this->openStackSwiftWrapper->sendFile(
            self::CONTAINER_NAME,
            $this->helios_files_upload_root."/".$transaction_info['sha1']
            );
        
        $this->heliosTransactionsSQL->setTransactionInCloud($transaction_info['id']);
        $this->logger->info("Stored file : {$transaction_info['sha1']}");
        return true;
    }

    public function deleteIfIsInCloud($sha1){
    	try {
			$file = $this->helios_files_upload_root . "/" . $sha1;
			if (!$this->openStackSwiftWrapper->fileExistsOnCloud(
				self::CONTAINER_NAME,
				$sha1
			)) {
				$this->logger->info("PES ALLER $sha1 not existing on cloud : not deleted");
				return false;
			}
			$this->logger->info("Deleting PES ALLER : $file");

			$filesystem = new Filesystem();
			$filesystem->remove($file);
			return true;
		}catch (Exception $e){
    		$this->logger->alert("Problème lors de la supression du PES ALLER $file : " . $e->getMessage());
    		return false;
		}
	}

	/**
	 * @param int $no_access_during_nb_days
	 * @throws Exception
	 */
    public function menageLocal($no_access_during_nb_days = 9999, $do = true){
		$sigtermHandler = SigTermHandler::getInstance();
        $dh = opendir($this->helios_files_upload_root);
        if (! $dh) {
            throw new UnrecoverableException("Impossible d'ouvrir " . $this->helios_files_upload_root);
        }

        while (($file = readdir($dh)) !== false) {
            if ($sigtermHandler->isSigtermCalled()){
                break;
            }
            if (in_array($file,array('.','..'))){
                continue;
            }
            if ($this->isRecentlyCreated($file,$no_access_during_nb_days)){
				$this->logger->debug("File $file too young to die : not deleted");
				continue;
            }
            if (! $this->openStackSwiftWrapper->fileExistsOnCloud(
                self::CONTAINER_NAME,
                $file
            )){
            	$this->logger->info("File $file not existing on cloud : not deleted");
                continue;
            }
			$this->logger->info("Deleting file : $file");
            if ($do) {
				unlink($this->helios_files_upload_root . "/" . $file);
			}
        }
        closedir($dh);
    }

    private function isRecentlyCreated($filename, $no_access_during_nb_days = 9999){
        $last_access_time = filemtime($this->helios_files_upload_root."/".$filename);
        $nb_seconds_without_access = time() - $last_access_time;
        $no_access_during_nb_seconds = $no_access_during_nb_days*86400;
        $this->logger->debug("Nombre de jour depuis la derniere modif : " . round($nb_seconds_without_access/60/60/24));
        return ($nb_seconds_without_access < $no_access_during_nb_seconds);
    }

}