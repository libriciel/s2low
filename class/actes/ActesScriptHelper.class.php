<?php

class ActesScriptHelper {

    private $actes_files_upload_root;
    private $actesTransactionsSQL;
    private $logger;
    private $actesEnvelopeSQL;

    public function __construct(
        $actes_files_upload_root,
        Logger $logger,
        ActesTransactionsSQL $actesTransactionsSQL,
        ActesEnvelopeSQL $actesEnvelopeSQL
    ) {
        $this->actes_files_upload_root = $actes_files_upload_root;
        $this->logger = $logger;
        $this->actesTransactionsSQL = $actesTransactionsSQL;
        $this->actesEnvelopeSQL = $actesEnvelopeSQL;
    }

    public function getArchivePath($enveloppe_id){
        $envelope_info = $this->actesEnvelopeSQL->getInfo($enveloppe_id);
        return $this->actes_files_upload_root . "/" . $envelope_info['file_path'];
    }

    public function updateStatus($transactions_ids,$status_id,$message,$flux_retour = ""){
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
            -1 => 'erreur', 'annule', 'poste', 'en attente','transmis','recu', 7=>'document reçu', 21=>"document reçu (pas d'AR)"
        );
        return $status_list[$status_id];
    }

}