<?php

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
        Logger $logger
    ) {
        $this->helios_files_upload_root = $helios_files_upload_root;
        $this->heliosTransactionsSQL = $heliosTransactionsSQL;
        $this->openStackSwiftWrapper = $openStackSwiftWrapper;
        $this->logger = $logger;
    }

    public function storeAll(){
        $result = $this->heliosTransactionsSQL->getAllTransactionToSendInCloud();
        
        foreach($result as $transaction_info){
            $this->storeNextFile($transaction_info);
        }
    }
    
    public function storeNextFile($transaction_info){
        
        $this->log(
            "Transaction {$transaction_info['id']} - ".
            "Fichier {$transaction_info['filename']} -".
            " {$transaction_info['sha1']}"
        );
        if ( ! file_exists($this->helios_files_upload_root."/".$transaction_info['sha1'])){
            return true;
        }
        
        echo "Depot du fichier : ".$this->helios_files_upload_root."/".$transaction_info['sha1'];
        $this->openStackSwiftWrapper->sendFile(
            self::CONTAINER_NAME,
            $this->helios_files_upload_root."/".$transaction_info['sha1']
            );
        
        $this->heliosTransactionsSQL->setTransactionInCloud($transaction_info['id']);
        $this->log("Fichier {$transaction_info['sha1']} envoyé");
    }

    public function menageLocal($no_access_during_nb_days = 9999){
        $dh = opendir($this->helios_files_upload_root);
        if (! $dh) {
            throw new Exception("Impossible d'ouvrir " . $this->helios_files_upload_root);
        }

        while (($file = readdir($dh)) !== false) {
            if (in_array($file,array('.','..'))){
                continue;
            }
            if ($this->isRecentlyCreated($file,$no_access_during_nb_days)){
                continue;
            }
            if (! $this->openStackSwiftWrapper->fileExistsOnCloud(
                self::CONTAINER_NAME,
                $file
            )){
                $this->log("Le fichier $file n'existe pas sur le cloud !");
                continue;
            }
            $this->log("Suppression du fichier $file");
            unlink($this->helios_files_upload_root."/".$file);
        }
        closedir($dh);
    }

    private function isRecentlyCreated($filename, $no_access_during_nb_days = 9999){
        $last_access_time = filectime($this->helios_files_upload_root."/".$filename);
        $nb_seconds_without_access = time() - $last_access_time;
        $no_access_during_nb_seconds = $no_access_during_nb_days*86400;
        return ($nb_seconds_without_access < $no_access_during_nb_seconds);
    }

    private function log($message){
        $this->logger->log("PesAllerStorage",$message);
    }

}