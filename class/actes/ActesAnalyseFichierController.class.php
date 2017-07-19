<?php

class ActesAnalyseFichierController {

    private $actes_appli_trigramme;
    private $actesTransactionsSQL;
    private $logger;
    private $actesEnvelopeSQL;
    private $actesScriptHelper;

    public function __construct(
        Logger $logger,
        ActesTransactionsSQL $actesTransactionsSQL,
        ActesEnvelopeSQL $actesEnvelopeSQL,
        $actes_appli_trigramme,
        ActesScriptHelper $actesScriptHelper
    ) {
        $this->actes_appli_trigramme = $actes_appli_trigramme;
        $this->logger = $logger;
        $this->actesTransactionsSQL = $actesTransactionsSQL;
        $this->actesEnvelopeSQL = $actesEnvelopeSQL;
        $this->actesScriptHelper = $actesScriptHelper;
    }

    private function log($message){
        $this->logger->log("actes-analyse-fichier-a-envoyer",$message);
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

        $archive_path =  $this->actesScriptHelper->getArchivePath($enveloppe_id);

        $this->log("[$envelope_libelle] Emplacement de l'archive :  $archive_path");

        $archive = new \Libriciel\LibActes\ArchiveValidator($this->actes_appli_trigramme);
        try {
            $archive->validate($archive_path);
        } catch (Exception $e){
            $message = utf8_decode( $e->getMessage());
            $this->log("[$envelope_libelle] L'archive n'est valide : $message");
            $this->actesScriptHelper->updateStatus($transaction_ids,ActesStatusSQL::STATUS_EN_ERREUR,"Enveloppe invalide : $message");
            return false;
        }
        $this->log("[$envelope_libelle] L'archive est valide !");
        $this->actesScriptHelper->updateStatus(
            $transaction_ids,
            ActesStatusSQL::STATUS_EN_ATTENTE_DE_TRANSMISSION,
            "Accepte par le TdT : validation OK"
        );

        return true;
    }

}