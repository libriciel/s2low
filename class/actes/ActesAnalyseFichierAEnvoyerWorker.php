<?php

namespace S2lowLegacy\Class\actes;

use Error;
use S2lowLegacy\Class\IWorker;
use S2lowLegacy\Class\PadesValid;
use S2lowLegacy\Class\RecoverableException;
use S2lowLegacy\Class\S2lowLogger;
use S2lowLegacy\Class\TmpFolder;
use S2lowLegacy\Class\WorkerScript;
use Exception;
use Libriciel\LibActes\ArchiveValidator;
use S2low\Services\PdfValidator;

class ActesAnalyseFichierAEnvoyerWorker implements IWorker
{
    public const QUEUE_NAME = "actes-analyze-fichier-a-envoyer";
    public const PHEANSTALK_TTR = 360;

    private $actes_appli_trigramme;
    private $actes_appli_quadrigramme;
    private $actesTransactionsSQL;
    private $logger;
    private $actesScriptHelper;
    private $padesValid;
    private $workerScript;
    private $actes_dont_valid_signing_certificate;
    private $actesTypePJSQL;
    /** @var \S2low\Services\PdfValidator  */
    private $pdfValidator;
    /**
     * @var \S2lowLegacy\Class\actes\ArchiveValidatorFactory
     */
    private ArchiveValidatorFactory $archiveValidatorFactory;

    public function __construct(
        S2lowLogger $logger,
        ActesTransactionsSQL $actesTransactionsSQL,
        $actes_appli_trigramme,
        $actes_appli_quadrigramme,
        ActesScriptHelper $actesScriptHelper,
        PadesValid $padesValid,
        WorkerScript $workerScript,
        $actes_dont_valid_signing_certificate,
        ActesTypePJSQL $actesTypePJSQL,
        PdfValidator $pdfValidator,
        ArchiveValidatorFactory $archiveValidatorFactory
    ) {
        $this->actes_appli_trigramme = $actes_appli_trigramme;
        $this->actes_appli_quadrigramme = $actes_appli_quadrigramme;
        $this->logger = $logger;
        $this->actesTransactionsSQL = $actesTransactionsSQL;
        $this->actesScriptHelper = $actesScriptHelper;
        $this->padesValid = $padesValid;
        $this->workerScript = $workerScript;
        $this->actes_dont_valid_signing_certificate = $actes_dont_valid_signing_certificate;
        $this->actesTypePJSQL = $actesTypePJSQL;
        $this->pdfValidator = $pdfValidator;
        $this->archiveValidatorFactory = $archiveValidatorFactory;
    }

    public function getQueueName()
    {
        return self::QUEUE_NAME;
    }

    public function getData($id)
    {
        return $id;
    }

    public function getAllId()
    {
        return $this->actesTransactionsSQL->getEnveloppeIdByTransactionsStatus(ActesStatusSQL::STATUS_POSTE);
    }

    /**
     * @param $data - envl
     * @return bool
     * @throws Exception
     */
    public function work($data)
    {
        $enveloppe_id = $data;
        $transaction_ids = $this->actesTransactionsSQL->getIdByEnvelopeId($enveloppe_id);


        $envelope_libelle = "enveloppe $enveloppe_id (transactions " . implode(",", $transaction_ids) . ")";

        $this->logger->info("[$envelope_libelle] Analyse");

        $archive_path =  $this->actesScriptHelper->getArchivePath($enveloppe_id);

        if (!$archive_path) {
            $this->logger->error("[$envelope_libelle] Non trouvée en local ou sur le cloud");
            throw new RecoverableException("[$envelope_libelle] Non trouvée en local ou sur le cloud");
        }

        $this->logger->debug("[$envelope_libelle] Emplacement de l'archive :  $archive_path");

        $this->logger->debug("id_tdt : {$this->actes_appli_trigramme}, id_appli : {$this->actes_appli_quadrigramme}");


        $must_validate_certificate = $this->mustValidateCertificate($transaction_ids);

        $archive = $this->archiveValidatorFactory->get(
            $this->actes_appli_trigramme,
            $this->actes_appli_quadrigramme,
            true
        );
        $tmpFolder = new TmpFolder();
        $tmp_dir = $tmpFolder->create();


        $all_type_pj = $this->actesTypePJSQL->getCodeList();

        try {
            try {
                $archive->setValidationTypologieByNature($this->actesTypePJSQL->getListByNature());
                $archive->validate(
                    $archive_path,
                    [1, 2, 3, 4, 5, 6],
                    $all_type_pj
                );
            } catch (Exception $e) {
                throw new Exception($e->getMessage(), $e->getCode(), $e);
            } catch (Error $error) {
                throw new Exception($error->getMessage(), $error->getCode(), $error);
            }
            $this->validatePades($archive_path, $tmp_dir, $must_validate_certificate);
        } catch (RecoverableException $e) {
            $tmpFolder->delete($tmp_dir);
            $this->logger->error("[$envelope_libelle] : erreur lors de l'analyse PADES VALID : " . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            $tmpFolder->delete($tmp_dir);
            $message = $e->getMessage();
            $this->logger->notice("[$envelope_libelle] L'archive n'est valide : $message");
            $this->actesScriptHelper->updateStatusAndLog($transaction_ids, ActesStatusSQL::STATUS_EN_ERREUR, "Enveloppe invalide : $message");
            return false;
        }

        $tmpFolder->delete($tmp_dir);
        $this->logger->info("[$envelope_libelle] L'archive est valide !");

        $this->actesScriptHelper->updateStatusAndLog(
            $transaction_ids,
            ActesStatusSQL::STATUS_EN_ATTENTE_DE_TRANSMISSION,
            "Accepté par le TdT : validation OK"
        );

        $this->workerScript->putJobByQueueName(
            ActesEnvoiFichierWorker::QUEUE_NAME,
            $enveloppe_id,
            ActesEnvoiFichierWorker::PHEANSTALK_TTR
        );
        return true;
    }

    /**
     * @param $archive_filepath
     * @param $tmp_dir
     * @throws RecoverableException
     * @throws Exception
     */
    private function validatePades($archive_filepath, $tmp_dir, $must_validate_certificate)
    {
        $archive = new \Libriciel\LibActes\Archive();

        $archiveData = $archive->getArchiveDataFromTarball($archive_filepath, $tmp_dir);

        foreach ($archiveData->fichierXML as $fichierXML) {
            foreach ($fichierXML->getFileList() as $filekey) {
                if (is_array($fichierXML->$filekey)) {
                    foreach ($fichierXML->$filekey as $i => $filepath) {
                        $this->validatePADESOneFile($filepath, $must_validate_certificate);
                    }
                } else {
                    $this->validatePADESOneFile($fichierXML->$filekey, $must_validate_certificate);
                }
            }
        }
    }

    /**
     * @param $filepath
     * @param $must_validate_certificate
     * @throws RecoverableException
     */
    private function validatePADESOneFile($filepath, $must_validate_certificate)
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $filepath);
        finfo_close($finfo);
        if ($mime_type != 'application/pdf') {
            return;
        }

        $this->pdfValidator->check($filepath);
        try {
            $this->padesValid->validate($filepath, $must_validate_certificate);
        } catch (RecoverableException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new Exception("Problème sur " . basename($filepath) . " : " . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     *
     * On ne valide pas le certificat sur les marchés publics car les soumissionnaire peuvent le signer avec n'importe quel certificat
     * Cela n'est de toute manière pas une exigence.
     * On le fait sur le reste pour s'assurer que la collectivité signe avec des certificat valides (délib, arreté, ...)
     *
     *
     * @param $transaction_ids
     * @return bool
     */
    private function mustValidateCertificate($transaction_ids)
    {
        if ($this->actes_dont_valid_signing_certificate) {
            return false;
        }
        $transactions_info = $this->actesTransactionsSQL->getInfo($transaction_ids[0]);

        $this->logger->debug("test {$transaction_ids[0]}", $transactions_info);

        if ($transactions_info['nature_code'] != 4 || mb_substr($transactions_info['classification'], 0, 3) != '1.1') {
            $this->logger->debug("validate certificate on");
            return true;
        }
        $this->logger->debug("validate certificate off");
        return false;
    }

    public function getMutexName($data)
    {
        return sprintf("actes-transaction-%s", $data);
    }

    public function isDataValid($data)
    {
        $enveloppe_id = $data;
        $result = false;
        $transaction_ids = $this->actesTransactionsSQL->getIdByEnvelopeId($enveloppe_id);
        foreach ($transaction_ids as $transaction_id) {
            $transaction_info = $this->actesTransactionsSQL->getInfo($transaction_id);
            /* bof... */
            $result = $result || ($transaction_info['last_status_id'] == ActesStatusSQL::STATUS_POSTE);
        }
        return $result;
    }

    /**
     * @return void
     */
    public function start()
    {
        // TODO: Implement start() method.
    }

    /**
     * @return void
     */
    public function end()
    {
        // TODO: Implement end() method.
    }
}
