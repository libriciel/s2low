<?php

class ActesEnvoiFichierController {

    private $actes_files_upload_root;
    private $actesTransactionsSQL;
    private $logger;
    private $actesEnvelopeSQL;
    private $actesScriptHelper;
    private $actesTransmissionWindowsSQL;
    private $actesFileSender;

    public function __construct(
        $actes_files_upload_root,
        Logger $logger,
        ActesTransactionsSQL $actesTransactionsSQL,
        ActesEnvelopeSQL $actesEnvelopeSQL,
        ActesScriptHelper $actesScriptHelper,
        ActesTransmissionWindowsSQL $actesTransmissionWindowsSQL,
        ActesFileSender $actesFileSender
    ) {
        $this->actes_files_upload_root = $actes_files_upload_root;
        $this->logger = $logger;
        $this->actesTransactionsSQL = $actesTransactionsSQL;
        $this->actesEnvelopeSQL = $actesEnvelopeSQL;
        $this->actesScriptHelper = $actesScriptHelper;
        $this->actesTransmissionWindowsSQL = $actesTransmissionWindowsSQL;
        $this->actesFileSender = $actesFileSender;
    }

    private function log($message){
        $this->logger->log("actes-envoi-fichier",$message);
    }

    public function sendAllEnvelopes(){
        $this->log("Lancement du script");
        $enveloppe_ids = $this->actesTransactionsSQL->getEnveloppeIdByTransactionsStatus(ActesStatusSQL::STATUS_EN_ATTENTE_DE_TRANSMISSION);
        $this->log("Envoie de ".count($enveloppe_ids)." enveloppes de transaction à l'état EN ATTENTE DE TRANSMISSION");
        foreach($enveloppe_ids as $enveloppe_id){
            $this->envoiEnveloppe($enveloppe_id);
        }
        $this->log("Fin du script");
        return true;
    }

    public function envoiEnveloppe($enveloppe_id){
        $transaction_ids = $this->actesTransactionsSQL->getIdByEnvelopeId($enveloppe_id);

        $envelope_libelle = "enveloppe $enveloppe_id (transactions ".implode(",",$transaction_ids).")";

        $this->log("[$envelope_libelle] Envoi");
        $envelope_info = $this->actesEnvelopeSQL->getInfo($enveloppe_id);

        if (! $this->actesTransmissionWindowsSQL->canSend($envelope_info['file_size'])) {
            $this->log("[$envelope_libelle] Impossible d'envoyer la transaction : la fenêtre est pleine");
            return false;
        }
        try {
            $archive_path =  $this->actesScriptHelper->getArchivePath($enveloppe_id);
            $this->actesFileSender->send($archive_path);
        } catch(Exception $e){
            $message = utf8_decode( $e->getMessage());
            $this->log("[$envelope_libelle] Impossible d'envoyer l'archive : $message");
            return false;
        }
        $this->log("[$envelope_libelle] L'archive a été envoyé");

        $this->actesScriptHelper->updateStatus(
            $transaction_ids,
            ActesStatusSQL::STATUS_TRANSMIS,
            "Transmis au MIOCT"
        );

        $this->actesTransmissionWindowsSQL->addFile($envelope_info['file_size']);

        return true;
    }

}