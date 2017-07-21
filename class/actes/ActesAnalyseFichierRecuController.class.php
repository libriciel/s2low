<?php

use Libriciel\LibActes\FichierXML\MessageMetierARActes;

class ActesAnalyseFichierRecuController {

    private $logger;
    private $actes_response_tmp_local_path;
    private $actes_response_error_path;
    private $actesTransactionsSQL;
    private $actesScriptHelper;

    public function __construct(
        Logger $logger,
        $actes_response_tmp_local_path,
        $actes_response_error_path,
        ActesTransactionsSQL $actesTransactionsSQL,
        ActesScriptHelper $actesScriptHelper
    ) {
        $this->logger = $logger;
        $this->actes_response_tmp_local_path = $actes_response_tmp_local_path;
        $this->actes_response_error_path = $actes_response_error_path;
        $this->actesTransactionsSQL = $actesTransactionsSQL;
        $this->actesScriptHelper = $actesScriptHelper;
    }

    public function analyseAll(){
        $this->log("Début du script");
        $this->log("Analyse du répertoire : {$this->actes_response_tmp_local_path}");

        $file_list = @ scandir($this->actes_response_tmp_local_path);

        if ($file_list === false){
            $message = "Erreur lors de la lecture du répertoire  $this->actes_response_tmp_local_path";
            $this->log($message);
            throw new Exception($message);
        }
        $file_list = array_diff($file_list, array('..', '.'));

        if (!$file_list){
            $this->log("Aucun répertoire à analyser");
            return true;
        }
        $this->log("Traitement de ".count($file_list)." répertoire trouvés");

        foreach($file_list as $file){
           $this->analyseOneFileMoveIfError($file);
        }

        $this->log("Fin du script");
        return true;
    }

    private function analyseOneFileMoveIfError($file){
        $rep_path = $this->actes_response_tmp_local_path."/".$file;
        try {
            $this->analyseOneFile($rep_path);
        } catch (Exception $e){
            $this->log("Echec du traitement de $rep_path : " . $e->getMessage());
            $this->log("Déplacement du répertoire $file vers {$this->actes_response_error_path}");
            rename($rep_path,$this->actes_response_error_path."/".$file);
        }
    }

    public function analyseOneFile($rep_path){

        $this->log("Traitement de $rep_path");

        $archive = new \Libriciel\LibActes\Archive();

        $archiveData = $archive->getArchiveDataFromFolder($rep_path);

        foreach($archiveData->fichierXML as $fichierXML){
            $code_message = $fichierXML->getCodeMessage();
            $this->log("Code message : $code_message");
            if ($code_message == MessageMetierARActes::CODE_MESSAGE){
                /** @var MessageMetierARActes $fichierXML */
                $this->traitementARActe($fichierXML);
            } else {
                //TODO on mets à jour la classification

                //TODO on crée une transaction complémentaire

                //TODO on traite l'anomalie

                //...
                throw new Exception("Code message $code_message non géré");
            }
            $tmpDir = new TmpFolder();
            $this->log("Suppression du répertoire $rep_path");
            $tmpDir->delete($rep_path);
        }
    }

    private function traitementARActe(MessageMetierARActes $fichierXML){
        $this->log("AR Actes trouvé pour l'acte : " . $fichierXML->id_actes);
        $transaction_id = $this->actesTransactionsSQL->getBySirenAndNumeroInterne($fichierXML->siren,$fichierXML->numero_interne);
        if (! $transaction_id){
            throw new Exception(
                "Aucune transation trouver pour le couple SIREN {$fichierXML->siren} - numéro interne {$fichierXML->numero_interne}"
            );
        }
        $this->log("$fichierXML->id_actes -> transaction_id = $transaction_id");
        $message = "Recu par le MIOCT le ".$fichierXML->date_reception;

        $xml = file_get_contents($fichierXML->file_path);
        $this->log($message);
        $this->actesScriptHelper->updateStatus(
            array($transaction_id),
            ActesStatusSQL::STATUS_ACQUITTEMENT_RECU,
            $message,
            $xml
        );
    }

    private function log($message){
        $this->logger->log("actes-analyse-fichier-recu",$message);
    }

}