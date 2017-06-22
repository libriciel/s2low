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
        while($this->storeNextFile()){
            //empty
        }
    }

    public function storeNextFile(){
        $transaction_info = $this->heliosTransactionsSQL->getNextTransactionToSendInCloud();
        if (! $transaction_info){
            $this->log("Il n'y a plus aucune transaction uniquement en local");
            return false;
        }
        $this->log(
            "Transaction {$transaction_info['id']} - ".
            "Fichier {$transaction_info['filename']} -".
            " {$transaction_info['sha1']}"
        );

        $this->openStackSwiftWrapper->sendFile(
            self::CONTAINER_NAME,
            $this->helios_files_upload_root."/".$transaction_info['sha1']
        );

        $this->heliosTransactionsSQL->setTransactionInCloud($transaction_info['id']);
        $this->log("Fichier {$transaction_info['sha1']} envoyé");
        return true;
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
            if ($this->isRecentlyAcceded($file,$no_access_during_nb_days)){
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

    private function isRecentlyAcceded($filename,$no_access_during_nb_days = 9999){
        $last_access_time = fileatime($this->helios_files_upload_root."/".$filename);
        $nb_seconds_without_access = time() - $last_access_time;
        $no_access_during_nb_seconds = $no_access_during_nb_days*86400;
        return ($nb_seconds_without_access < $no_access_during_nb_seconds);
    }

    private function log($message){
        $this->logger->log("PesAllerStorage",$message);
    }

}