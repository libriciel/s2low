<?php

use Libriciel\LibActes\ArchiveData;

use Libriciel\LibActes\FichierXML\MessageMetierARActes;
use Libriciel\LibActes\FichierXML\MessageMetieAnomalieActe;
use Libriciel\LibActes\FichierXML\MessageMetierARAnnulation;
use Libriciel\LibActes\FichierXML\MessageMetierARPieceComplementaire;
use Libriciel\LibActes\FichierXML\MessageMetierARReponseRejetLettreObservations;
use Libriciel\LibActes\FichierXML\MessageMetierCourrierSimple;
use Libriciel\LibActes\FichierXML\MessageMetierDefereTA;
use Libriciel\LibActes\FichierXML\MessageMetierDemandePieceComplementaire;
use Libriciel\LibActes\FichierXML\MessageMetierLettreObservations;
use Libriciel\LibActes\FichierXML\MessageMetierReponseClassificationSansChangement;
use Libriciel\LibActes\FichierXML\MessageMetierRetourClassification;

class ActesAnalyseFichierRecuController {

    private $logger;
    private $actes_response_tmp_local_path;
    private $actes_response_error_path;
    private $actesTransactionsSQL;
    private $actesScriptHelper;
    private $actesUpdateClassificationSQL;
    private $actesEnvelopeSQL;
    private $actes_files_upload_root;
    private $actesIncludedFileSQL;
    private $actes_ministere_acronyme;

    public function __construct(
        Logger $logger,
        $actes_response_tmp_local_path,
        $actes_response_error_path,
        ActesTransactionsSQL $actesTransactionsSQL,
        ActesScriptHelper $actesScriptHelper,
        ActesUpdateClassificationSQL $actesUpdateClassificationSQL,
        ActesEnvelopeSQL $actesEnvelopeSQL,
        ActesIncludedFileSQL $actesIncludedFileSQL,
        $actes_files_upload_root,
        $actes_ministere_acronyme
    ) {
        $this->logger = $logger;
        $this->actes_response_tmp_local_path = $actes_response_tmp_local_path;
        $this->actes_response_error_path = $actes_response_error_path;
        $this->actesTransactionsSQL = $actesTransactionsSQL;
        $this->actesScriptHelper = $actesScriptHelper;
        $this->actesUpdateClassificationSQL = $actesUpdateClassificationSQL;
        $this->actesEnvelopeSQL = $actesEnvelopeSQL;
        $this->actes_files_upload_root = $actes_files_upload_root;
        $this->actesIncludedFileSQL = $actesIncludedFileSQL;
        $this->actes_ministere_acronyme = $actes_ministere_acronyme;
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
        $sigtermHandler = new SigTermHandler();
        foreach($file_list as $file){
           $this->analyseOneFileMoveIfError($file);
            if ($sigtermHandler->isSigtermCalled()){
                break;
            }
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

        try {
            $archiveData = $archive->getArchiveDataFromFolder($rep_path);
        } catch(Exception $e){
            throw new Exception(utf8_decode($e->getMessage()));
        }

        if ($archiveData->is_ano){
            $this->traitementEnveloppeAnomalie($archiveData);
            return;
        }

        foreach($archiveData->fichierXML as $fichierXML){
            $code_message = $fichierXML->getCodeMessage();
            $this->log("Code message : $code_message");
            if ($code_message == MessageMetierARActes::CODE_MESSAGE) {
                /** @var MessageMetierARActes $fichierXML */
                $this->traitementARActe($fichierXML);
            } elseif ($code_message == MessageMetierReponseClassificationSansChangement::CODE_MESSAGE){
                /** @var MessageMetierReponseClassificationSansChangement $fichierXML */
                $this->traitementRetourClassificationSansChangement($fichierXML);
            } elseif ($code_message == MessageMetierRetourClassification::CODE_MESSAGE){
                /** @var MessageMetierRetourClassification $fichierXML */
                $this->traitementRetourClassification($fichierXML);
            } elseif ($code_message == MessageMetierARAnnulation::CODE_MESSAGE){
                /** @var MessageMetierARAnnulation $fichierXML */
                $this->traitementARAnnulation($fichierXML);
            } elseif (in_array($code_message,array
                (
                    MessageMetierCourrierSimple::CODE_MESSAGE,
                    MessageMetierDemandePieceComplementaire::CODE_MESSAGE,
                    MessageMetierLettreObservations::CODE_MESSAGE,
                    MessageMetierDefereTA::CODE_MESSAGE,
                )
            )){
                $this->traitementDocumentRecu($archiveData);
            } elseif ($code_message == MessageMetieAnomalieActe::CODE_MESSAGE){
                /** @var MessageMetieAnomalieActe $fichierXML */
                $this->traitementAnomalie($fichierXML);
            } elseif ($code_message == MessageMetierARPieceComplementaire::CODE_MESSAGE){
                /** @var MessageMetierARPieceComplementaire $fichierXML */
                $this->traitementARPC($fichierXML);
            } elseif ($code_message == MessageMetierARReponseRejetLettreObservations::CODE_MESSAGE){
                /** @var MessageMetierARReponseRejetLettreObservations $fichierXML */
                $this->traitementARReponseLO($fichierXML);
            } else {
                throw new Exception("Code message $code_message non géré");
            }
            $tmpDir = new TmpFolder();
            $this->log("Suppression du répertoire $rep_path");
            $tmpDir->delete($rep_path);
        }
    }

    private function traitementEnveloppeAnomalie(ArchiveData $archiveData){
        $enveloppe_anomalie_name = basename($archiveData->enveloppe_path);
        $this->log("Anomalie trouvée pour l'enveloppe $enveloppe_anomalie_name");
        $envelope_id = $this->actesEnvelopeSQL->findByAnomalieEnveloppeName($enveloppe_anomalie_name);
        if (! $envelope_id){
            throw new Exception("L'enveloppe d'anomalie $enveloppe_anomalie_name ne correspond à aucune enveloppe de la base");
        }
        $this->log("Enveloppe $envelope_id trouvé pour l'anomalie $enveloppe_anomalie_name");

        $transaction_ids = $this->actesTransactionsSQL->getIdByEnvelopeId($envelope_id);

        $actesXML = new \Libriciel\LibActes\ActesXML();
        /** @var \Libriciel\LibActes\FichierXML\EnveloppeAnomalie $anomalieEnveloppe */
        $anomalieEnveloppe = $actesXML->getDataFromXML(file_get_contents($archiveData->enveloppe_path));

        $detail_erreur = utf8_decode($anomalieEnveloppe->detail_erreur);

        $message = "Enveloppe rejetée par le {$this->actes_ministere_acronyme} ({$anomalieEnveloppe->nature_erreur} : $detail_erreur)";
        $xml = file_get_contents($archiveData->enveloppe_path);

        $this->updateStatus(
            $transaction_ids,
            ActesStatusSQL::STATUS_EN_ERREUR,
            $message,
            $xml
        );
    }

    private function traitementDocumentRecu(ArchiveData $archiveData){
        $fichierXML = $archiveData->fichierXML;
        $fichierXML = $fichierXML[0];

        $transaction_id = $this->getBySirenAndNumeroInterne($fichierXML->siren,$fichierXML->numero_interne);

        $archive_folder = $this->actes_files_upload_root."/{$fichierXML->siren}/{$fichierXML->numero_interne}";

        $archiveData->id_tdt = ACTES_APPLI_TRIGRAMME;

        $archive = new \Libriciel\LibActes\Archive();

        $archive_path = $archive->generateZip($archiveData,$archive_folder);
        $envelope_path = substr($archive_path, strlen($this->actes_files_upload_root));
        $envelope_size = filesize($archive_path);

        $this->log("Archive enregistré dans $archive_path");

        $transaction_info = $this->actesTransactionsSQL->getInfo($transaction_id);

        $related_envelope_id = $this->actesEnvelopeSQL->createRelatedEnveloppe(
            $transaction_info['envelope_id'],
            $envelope_path,$envelope_size
        );
        $this->log("Création de l'enveloppe $related_envelope_id");

        $related_transaction_id = $this->actesTransactionsSQL->createRelatedTransaction(
            $related_envelope_id,
            substr($fichierXML->getCodeMessage(),0,1),
            date("Y-m-d H:i:s"),
            $transaction_id
        );

        $this->log("Création de la transaction $related_transaction_id");


        foreach($fichierXML->getFileList() as $item){
            if (is_array($fichierXML->$item)){
                foreach($fichierXML->$item as $i => $sub_item){
                    $this->addFile($related_envelope_id,$related_transaction_id,$sub_item);
                }
            } else {
                $this->addFile($related_envelope_id,$related_transaction_id,$fichierXML->$item);
            }
        }

        if (in_array($fichierXML->getCodeMessage(),
            array(MessageMetierCourrierSimple::CODE_MESSAGE,
                MessageMetierDefereTA::CODE_MESSAGE)
        )){
            $message = "Reçu par le Tdt (pas d'AR envoyé)";
            $status = ActesStatusSQL::STATUS_DOCUMENT_RECU_PAS_DAR;
        } else {
            $message = "Reçu par le Tdt";
            $status = ActesStatusSQL::STATUS_DOCUMENT_RECU;
        }
        $this->updateStatus(
            $related_transaction_id,
            $status,
            $message
        );


    }

    private function addFile($related_envelope_id,$related_transaction_id,$filepath){
        $finfo = new finfo();
        $filename = basename($filepath);
        $filesize = filesize($filepath);
        $content_type = $finfo->file($filepath,FILEINFO_MIME_TYPE);
        $file_id = $this->actesIncludedFileSQL->addIncludedFile(
            $related_envelope_id,
            $related_transaction_id,
            $content_type,
            $filesize,
            $filename
        );
        $this->log("Attachement du fichier $file_id");
    }

    private function traitementARActe(MessageMetierARActes $fichierXML){
        $this->log("AR Actes trouvé pour l'acte : " . $fichierXML->id_actes);

        $transaction_id = $this->getBySirenAndNumeroInterne($fichierXML->siren,$fichierXML->numero_interne);

        $this->actesTransactionsSQL->setUniqueID($transaction_id,$fichierXML->id_actes);

        $this->log("$fichierXML->id_actes -> transaction_id = $transaction_id");

        $message = "Reçu par le {$this->actes_ministere_acronyme} le ".$fichierXML->date_reception;

        $xml = file_get_contents($fichierXML->file_path);

        $this->updateStatus(
            $transaction_id,
            ActesStatusSQL::STATUS_ACQUITTEMENT_RECU,
            $message,
            $xml
        );
    }

    private function traitementARPC(MessageMetierARPieceComplementaire $fichierXML){
        $this->log("AR Actes trouvé pour l'envoi de piece complementaire : " . $fichierXML->id_actes);

        $transaction_id = $this->getBySirenAndNumeroInterne($fichierXML->siren,$fichierXML->numero_interne,3,true);

        $this->log("$fichierXML->id_actes -> transaction_id = $transaction_id");
        $message = "Reçu par le {$this->actes_ministere_acronyme} le ".$fichierXML->date_reception;

        $xml = file_get_contents($fichierXML->file_path);

        $this->updateStatus(
            $transaction_id,
            ActesStatusSQL::STATUS_ACQUITTEMENT_RECU,
            $message,
            $xml
        );
    }

    private function traitementARReponseLO(MessageMetierARReponseRejetLettreObservations $fichierXML){
        $this->log("AR Actes trouvé pour l'envoi d'une réponse ou d'un refus à une lettre d'observation : " . $fichierXML->id_actes);

        $transaction_id = $this->getBySirenAndNumeroInterne($fichierXML->siren,$fichierXML->numero_interne,4,true);

        $this->log("$fichierXML->id_actes -> transaction_id = $transaction_id");
        $message = "Reçu par le {$this->actes_ministere_acronyme} le ".$fichierXML->date_reception;

        $xml = file_get_contents($fichierXML->file_path);

        $this->updateStatus(
            $transaction_id,
            ActesStatusSQL::STATUS_ACQUITTEMENT_RECU,
            $message,
            $xml
        );
    }

    private function traitementAnomalie(MessageMetieAnomalieActe $fichierXML){
        $this->log("Anomalie trouvé pour l'acte : " . $fichierXML->numero_interne);
        $transaction_id = $this->getBySirenAndNumeroInterne($fichierXML->siren,$fichierXML->numero_interne);
        $message = "Anomalie signalee par le {$this->actes_ministere_acronyme} : ".$fichierXML->nature_anomalie." - ".$fichierXML->detail_anomalie;
        $xml = file_get_contents($fichierXML->file_path);
        $this->updateStatus(
            $transaction_id,
            ActesStatusSQL::STATUS_EN_ERREUR,
            $message,
            $xml
        );
    }

    private function traitementRetourClassification(MessageMetierRetourClassification $fichierXML){
        $transaction_id = $this->actesTransactionsSQL->getLastDemandeClassificationTransmis($fichierXML->siren);
        if (! $transaction_id){
            throw new Exception("Aucune demande de classification transmise trouvée pour le siren {$fichierXML->siren}");
        }
        $this->actesUpdateClassificationSQL->updateClassification($fichierXML->siren,file_get_contents($fichierXML->file_path));
        $message = "Mise à jour de la classification (date de classification: {$fichierXML->date_classification})";
        $xml = file_get_contents($fichierXML->file_path);

        $this->updateStatus(
            $transaction_id,
            ActesStatusSQL::STATUS_ACQUITTEMENT_RECU,
            $message,
            $xml
        );
    }

    private function traitementRetourClassificationSansChangement(MessageMetierReponseClassificationSansChangement $fichierXML){
        $this->log("Classification sans changement reçu");
        $transaction_id = $this->actesTransactionsSQL->getLastDemandeClassificationTransmis($fichierXML->siren);
        if (! $transaction_id){
            throw new Exception("Aucune demande de classification transmise trouvée pour le siren {$fichierXML->siren}");
        }
        $message = "Classification sans changement (date de classification: {$fichierXML->date_classification})";
        $xml = file_get_contents($fichierXML->file_path);
        $this->updateStatus(
            $transaction_id,
            ActesStatusSQL::STATUS_ACQUITTEMENT_RECU,
            $message,
            $xml
        );
    }

    public function traitementARAnnulation(MessageMetierARAnnulation $fichierXML){
        $this->log("Annulation trouvé pour l'Acte : " . $fichierXML->id_actes);

        $transaction_id = $this->getBySirenAndNumeroInterne($fichierXML->siren,$fichierXML->numero_interne);

        $this->log("{$fichierXML->id_actes} -> transaction_id = $transaction_id");
        $message = "Annulation recu par le {$this->actes_ministere_acronyme} le ".$fichierXML->date_reception;

        $xml = file_get_contents($fichierXML->file_path);

        $this->updateStatus(
            $transaction_id,
            ActesStatusSQL::STATUS_ANNULER,
            $message,
            $xml
        );
        $transaction_annulation_id = $this->getBySirenAndNumeroInterne($fichierXML->siren,$fichierXML->numero_interne,6);

        $this->updateStatus(
            $transaction_annulation_id,
            ActesStatusSQL::STATUS_ACQUITTEMENT_RECU,
            $message,
            $xml
        );
    }

    private function log($message){
        $this->logger->log("actes-analyse-fichier-recu",$message);
    }


    private function updateStatus($transaction_ids,$status_id,$message,$xml=false){
        if (! is_array($transaction_ids)){
            $transaction_ids = array($transaction_ids);
        }
        $this->log($message);
        $this->actesScriptHelper->updateStatus(
            $transaction_ids,
            $status_id,
            $message,
            $xml
        );
    }

    private function getBySirenAndNumeroInterne($siren,$numeroInterne,$type=1,$type_reponse_not_null=false){
        $transaction_id = $this->actesTransactionsSQL->getBySirenAndNumeroInterne($siren,$numeroInterne,$type,$type_reponse_not_null);
        if (! $transaction_id){
            throw new Exception(
                "Aucune transation trouver pour le couple SIREN $siren - numéro interne $numeroInterne"
            );
        }
        $this->log("Transaction de type $type trouvé avec le SIREN $siren et le numéro interne $numeroInterne : $transaction_id ");
        return $transaction_id;
    }
}