<?php

class ActesAnalyseFichierController {

    private $actes_files_upload_root;
    private $actes_appli_trigramme;
    private $actesTransactionsSQL;
    private $logger;
    private $actesEnvelopeSQL;

    public function __construct(
        $actes_files_upload_root,
        Logger $logger,
        ActesTransactionsSQL $actesTransactionsSQL,
        ActesEnvelopeSQL $actesEnvelopeSQL,
        $actes_appli_trigramme
    ) {
        $this->actes_files_upload_root = $actes_files_upload_root;
        $this->actes_appli_trigramme = $actes_appli_trigramme;
        $this->logger = $logger;
        $this->actesTransactionsSQL = $actesTransactionsSQL;
        $this->actesEnvelopeSQL = $actesEnvelopeSQL;
    }

    private function log($message){
        $this->logger->log("Actes analyse fichier à envoyer",$message);
    }

    public function validateAllEnveloppe(){
        $this->log("Lancement du script");
        $enveloppe_ids = $this->actesTransactionsSQL->getEnveloppeIdByTransactionsStatus(ActesStatusSQL::STATUS_POSTE);
        $this->log("Analyse de ".count($enveloppe_ids)." enveloppe de transaction à l'état POSTE");
        foreach($enveloppe_ids as $enveloppe_id){
            $this->validateOneEnveloppe($enveloppe_id);
        }

        $this->log("Fin du script");
        return true;
    }

    public function validateOneEnveloppe($enveloppe_id){
        $transaction_ids = $this->actesTransactionsSQL->getIdByEnvelopeId($enveloppe_id);

        $envelope_libelle = "enveloppe $enveloppe_id (transactions ".implode(",",$transaction_ids).")";

        $this->log("[$envelope_libelle] Analyse");

        $envelope_info = $this->actesEnvelopeSQL->getInfo($enveloppe_id);

        $archive_path =  $this->actes_files_upload_root . "/" . $envelope_info['file_path'];

        $this->log("[$envelope_libelle] Emplacement de l'archive :  $archive_path");

        $archive = new \Libriciel\LibActes\ArchiveValidator($this->actes_appli_trigramme);
        try {
            $archive->validate($archive_path);
        } catch (Exception $e){
            $message = utf8_decode( $e->getMessage());
            $this->log("[$envelope_libelle] L'archive n'est valide : $message");
            $this->updateStatus($transaction_ids,ActesStatusSQL::STATUS_EN_ERREUR,"Enveloppe invalide : $message");
            return false;
        }
        $this->log("[$envelope_libelle] L'archive est valide !");
        $this->updateStatus(
            $transaction_ids,
            ActesStatusSQL::STATUS_EN_ATTENTE_DE_TRANSMISSION,
            "Accepte par le TdT : validation OK"
        );

        return true;
    }

    private function updateStatus($transactions_ids,$status_id,$message,$flux_retour = ""){
        foreach($transactions_ids as $transactions_id) {
            $this->actesTransactionsSQL->updateStatus($transactions_id,$status_id,$message,$flux_retour);
            $info = $this->actesTransactionsSQL->getInfo($transactions_id);
            //WTF : trouvé dans le code java... j'imagine que ca sert pour l'API ?
            $message_log = "L'archive $transactions_id passe à l'état " . $this->getStatusName($status_id);

            if ($status_id == ActesStatusSQL::STATUS_ACQUITTEMENT_RECU) {
                $message_log = "L'acte : $transactions_id passe en recu.";
            }
			if ($status_id == ActesStatusSQL::STATUS_ANNULER) {
                $message_log = "L'acte : $transactions_id passe en annulee.";
            }
            Log::newEntry(LOG_ISSUER_NAME,$message_log,1,false,"USER","actes",false,$info['user_id']);
        }
    }
    public function getStatusName($status_id){
        $status_list = array(
            -1 => 'erreur', 'annule', 'poste', 'en attente','transmis','recu'
        );
        return $status_list[$status_id];
    }

}