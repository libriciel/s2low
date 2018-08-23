<?php

class ActesAnalyseFichierAEnvoyerWorker implements IWorker {

	const QUEUE_NAME = "actes-analyze-fichier-a-envoyer";

    private $actes_appli_trigramme;
    private $actes_appli_quadrigramme;
    private $actesTransactionsSQL;
    private $logger;
    private $actesEnvelopeSQL;
    private $actesScriptHelper;
    private $padesValid;
    private $workerScript;

    public function __construct(
        S2lowLogger $logger,
        ActesTransactionsSQL $actesTransactionsSQL,
        ActesEnvelopeSQL $actesEnvelopeSQL,
        $actes_appli_trigramme,
		$actes_appli_quadrigramme,
        ActesScriptHelper $actesScriptHelper,
        PadesValid $padesValid,
		WorkerScript $workerScript
    ) {
        $this->actes_appli_trigramme = $actes_appli_trigramme;
        $this->actes_appli_quadrigramme = $actes_appli_quadrigramme;
        $this->logger = $logger;
        $this->actesTransactionsSQL = $actesTransactionsSQL;
        $this->actesEnvelopeSQL = $actesEnvelopeSQL;
        $this->actesScriptHelper = $actesScriptHelper;
        $this->padesValid = $padesValid;
        $this->workerScript = $workerScript;
    }

	public function getQueueName(){
    	return self::QUEUE_NAME;
	}

	public function getData($id){
    	return $id;
	}

	public function getAllId(){
		return $this->actesTransactionsSQL->getEnveloppeIdByTransactionsStatus(ActesStatusSQL::STATUS_POSTE);
	}

	/**
	 * @param $data - envl
	 * @return bool
	 * @throws Exception
	 */
	public function work($data){
		$enveloppe_id = $data;
        $transaction_ids = $this->actesTransactionsSQL->getIdByEnvelopeId($enveloppe_id);


        $envelope_libelle = "enveloppe $enveloppe_id (transactions ".implode(",",$transaction_ids).")";

		$this->logger->info("[$envelope_libelle] Analyse");

        $archive_path =  $this->actesScriptHelper->getArchivePath($enveloppe_id);

		$this->logger->debug("[$envelope_libelle] Emplacement de l'archive :  $archive_path");

		$this->logger->debug("id_tdt : {$this->actes_appli_trigramme}, id_appli : {$this->actes_appli_quadrigramme}");

        $archive = new \Libriciel\LibActes\ArchiveValidator($this->actes_appli_trigramme,$this->actes_appli_quadrigramme);
        $tmpFolder = new TmpFolder();
        $tmp_dir = $tmpFolder->create();
        try {
        	try {
				$archive->validate($archive_path);
			} catch (Exception $e){
				throw new Exception(utf8_decode($e->getMessage()),$e->getCode(),$e);
			}
			$this->validatePades($archive_path,$tmp_dir);
		} catch (RecoverableException $e){
			$tmpFolder->delete($tmp_dir);
			$this->logger->error("[$envelope_libelle] : erreur lors de l'analyse PADES VALID : ". $e->getMessage());
			throw $e;
        } catch (Exception $e){
            $tmpFolder->delete($tmp_dir);
            $message = $e->getMessage();
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
		$this->workerScript->putJobByClassName(ActesEnvoiFichierWorker::class,$enveloppe_id);
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
	 * @throws Exception
	 */
    private function validatePADESOneFile($filepath){
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $filepath);
        finfo_close($finfo);
        if ($mime_type != 'application/pdf') {
            return;
        }
        try {
			$this->padesValid->validate($filepath);
		} catch(RecoverableException $e){
        	throw $e;
		} catch (Exception $e){
        	throw new Exception("Problème sur ".basename($filepath)." : " . $e->getMessage(),$e->getCode(),$e);
		}
    }

}