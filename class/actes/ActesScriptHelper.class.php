<?php

class ActesScriptHelper {

    private $actes_files_upload_root;
    private $actesTransactionsSQL;
    private $logger;
    private $actesEnvelopeSQL;
    private $actes_appli_trigramme;

    public function __construct(
        $actes_files_upload_root,
        Logger $logger,
        ActesTransactionsSQL $actesTransactionsSQL,
        ActesEnvelopeSQL $actesEnvelopeSQL,
        $actes_appli_trigramme
    ) {
        $this->actes_files_upload_root = $actes_files_upload_root;
        $this->logger = $logger;
        $this->actesTransactionsSQL = $actesTransactionsSQL;
        $this->actesEnvelopeSQL = $actesEnvelopeSQL;
        $this->actes_appli_trigramme = $actes_appli_trigramme;

    }

    public function getArchivePath($enveloppe_id){
        $envelope_info = $this->actesEnvelopeSQL->getInfo($enveloppe_id);
        return $this->actes_files_upload_root . "/" . $envelope_info['file_path'];
    }

    public function updateStatus($transactions_ids,$status_id,$message,$flux_retour = ""){


        foreach($transactions_ids as $transactions_id) {
            $this->actesTransactionsSQL->updateStatus($transactions_id,$status_id,$message,$flux_retour);
            $info = $this->actesTransactionsSQL->getInfo($transactions_id);

            $envelope_info = $this->actesEnvelopeSQL->getInfo($info['envelope_id']);

            $message_log =
                sprintf(
                    "[%s] Transaction %s (%d) : passage à l'état %s. Numéro SIREN de la collectivité : %s. Type de transaction: %d",
                    $this->actes_appli_trigramme,
                    $info['unique_id']?:$this->actesTransactionsSQL->guessUniqueId($transactions_id),
                    $transactions_id,
                    $this->getStatusName($status_id),
                    $envelope_info['siren'],
                    $info['type']
                );


            Log::newEntry(LOG_ISSUER_NAME,$message_log,1,false,"USER","actes",false,$info['user_id']);
        }
    }

    public function getStatusName($status_id){
        $status_list = array(
            -1 => 'erreur', 'annulé', 'posté', 'en attente','transmis','acquittement reçu', 7=>'document reçu','acquittement envoyé', 21=>"document reçu (pas d'AR)"
        );
        return $status_list[$status_id];
    }

}