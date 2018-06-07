<?php

class ActesAnalyseFichierAEnvoyerWorker {

    private $actes_appli_trigramme;
    private $actesTransactionsSQL;
    private $logger;
    private $actesEnvelopeSQL;
    private $actesScriptHelper;
    private $padesValid;

    public function __construct(
        S2lowLogger $logger,
        ActesTransactionsSQL $actesTransactionsSQL,
        ActesEnvelopeSQL $actesEnvelopeSQL,
        $actes_appli_trigramme,
        ActesScriptHelper $actesScriptHelper,
        PadesValid $padesValid
    ) {
        $this->actes_appli_trigramme = $actes_appli_trigramme;
        $this->logger = $logger;
        $this->actesTransactionsSQL = $actesTransactionsSQL;
        $this->actesEnvelopeSQL = $actesEnvelopeSQL;
        $this->actesScriptHelper = $actesScriptHelper;
        $this->padesValid = $padesValid;
    }
	/**
	 * @return bool
	 * @throws Exception
	 */
    public function validateAllEnveloppe(){
        $sigtermHandler = new SigTermHandler();
        $this->logger->debug("Lancement du script");
        $enveloppe_ids = $this->actesTransactionsSQL->getEnveloppeIdByTransactionsStatus(ActesStatusSQL::STATUS_POSTE);
		$this->logger->info("Analyse de ".count($enveloppe_ids)." enveloppe de transaction à l'état POSTE");
        foreach($enveloppe_ids as $enveloppe_id){
            $this->validateOneEnveloppe($enveloppe_id);
            if ($sigtermHandler->isSigtermCalled()){
                break;
            }
        }
		$this->logger->debug("Fin du script");
        return true;
    }

	/**
	 * @param $enveloppe_id
	 * @return bool
	 * @throws Exception
	 */
    public function validateOneEnveloppe($enveloppe_id){
        $transaction_ids = $this->actesTransactionsSQL->getIdByEnvelopeId($enveloppe_id);

        $envelope_libelle = "enveloppe $enveloppe_id (transactions ".implode(",",$transaction_ids).")";

		$this->logger->info("[$envelope_libelle] Analyse");

        $archive_path =  $this->actesScriptHelper->getArchivePath($enveloppe_id);

		$this->logger->debug("[$envelope_libelle] Emplacement de l'archive :  $archive_path");

        $archive = new \Libriciel\LibActes\ArchiveValidator($this->actes_appli_trigramme);
        $tmpFolder = new TmpFolder();
        $tmp_dir = $tmpFolder->create();
        try {
			$archive->validate($archive_path);
			$this->validatePades($archive_path,$tmp_dir);
		} catch (RecoverableException $e){
			$tmpFolder->delete($tmp_dir);
			$this->logger->error("[$envelope_libelle] : erreur lors de l'analyse PADES VALID : ". $e->getMessage());
			return false;
        } catch (Exception $e){
            $tmpFolder->delete($tmp_dir);
            $message = utf8_decode( $e->getMessage());
			$this->logger->notice("[$envelope_libelle] L'archive n'est valide : $message");
            $this->actesScriptHelper->updateStatus($transaction_ids,ActesStatusSQL::STATUS_EN_ERREUR,"Enveloppe invalide : $message");
            return false;
        }

        $tmpFolder->delete($tmp_dir);
		$this->logger->info("[$envelope_libelle] L'archive est valide !");
        $this->actesScriptHelper->updateStatus(
            $transaction_ids,
            ActesStatusSQL::STATUS_EN_ATTENTE_DE_TRANSMISSION,
            "Accepté par le TdT : validation OK"
        );

        return true;
    }

	/**
	 * @param $archive_filepath
	 * @param $tmp_dir
	 * @throws RecoverableException
	 * @throws Exception
	 */
    private function validatePades($archive_filepath,$tmp_dir){
        $archive = new \Libriciel\LibActes\Archive();

        $archiveData = $archive->getArchiveDataFromTarball($archive_filepath,$tmp_dir);

        foreach($archiveData->fichierXML as $fichierXML) {
                foreach($fichierXML->getFileList() as $filekey){
                    if (is_array($fichierXML->$filekey)){
                        foreach($fichierXML->$filekey as $i => $filepath){
                            $this->validatePADESOneFile($filepath);
                        }
                    } else {
                        $this->validatePADESOneFile($fichierXML->$filekey);
                    }
            }
        }
    }

	/**
	 * @param $filepath
	 * @throws RecoverableException
	 */
    private function validatePADESOneFile($filepath){
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $filepath);
        finfo_close($finfo);
        if ($mime_type != 'application/pdf') {
            return;
        }
        $this->padesValid->validate($filepath);
    }

}