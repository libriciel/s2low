<?php

namespace S2lowLegacy\Class\actes;

use Error;
use Psr\Log\LoggerInterface;
use S2low\Exceptions\PadesValidConnectionException;
use S2low\Services\CloudFileStorageInterface;
use S2low\Services\LocalFileResolver;
use S2low\Services\Validators\PadesValidator;
use S2lowLegacy\Class\IWorker;
use S2lowLegacy\Class\PadesValid;
use S2lowLegacy\Class\RecoverableException;
use S2lowLegacy\Class\TmpFolder;
use S2lowLegacy\Class\WorkerScript;
use Exception;
use S2low\Services\PdfValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ActesAnalyseFichierAEnvoyerWorker implements IWorker
{
    public const QUEUE_NAME = "actes-analyze-fichier-a-envoyer";
    public const PHEANSTALK_TTR = 360;

    private $actes_appli_trigramme;
    private $actes_appli_quadrigramme;
    private $actesTransactionsSQL;
    private $logger;
    private $actesScriptHelper;
    private $workerScript;
    private $actesTypePJSQL;
    /** @var \S2low\Services\PdfValidator  */
    private $pdfValidator;
    /**
     * @var \S2lowLegacy\Class\actes\ArchiveValidatorFactory
     */
    private ArchiveValidatorFactory $archiveValidatorFactory;

    public function __construct(
        LoggerInterface $logger,
        ActesTransactionsSQL $actesTransactionsSQL,
        $actes_appli_trigramme,
        $actes_appli_quadrigramme,
        ActesScriptHelper $actesScriptHelper,
        WorkerScript $workerScript,
        ActesTypePJSQL $actesTypePJSQL,
        PdfValidator $pdfValidator,
        ArchiveValidatorFactory $archiveValidatorFactory,
        #[Autowire(service: 'app.localFileResolver.acte_enveloppe')]
        private readonly LocalFileResolver $localFileResolver,
        #[Autowire(service: 'app.store.file.acte_enveloppe')]
        private readonly CloudFileStorageInterface $cloudActeStorage,
        private readonly PadesValidator $padesValidator
    ) {
        $this->actes_appli_trigramme = $actes_appli_trigramme;
        $this->actes_appli_quadrigramme = $actes_appli_quadrigramme;
        $this->logger = $logger;
        $this->actesTransactionsSQL = $actesTransactionsSQL;
        $this->actesScriptHelper = $actesScriptHelper;
        $this->workerScript = $workerScript;
        $this->actesTypePJSQL = $actesTypePJSQL;
        $this->pdfValidator = $pdfValidator;
        $this->archiveValidatorFactory = $archiveValidatorFactory;
    }

    public function getQueueName(): string
    {
        return self::QUEUE_NAME;
    }

    public function getData($id): mixed
    {
        return $id;
    }

    public function getAllId(): array
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

        $archive_path =  $this->localFileResolver->getFullPath($enveloppe_id);
        $this->cloudActeStorage->downloadFileFromCloud($enveloppe_id);

        if (!file_exists($archive_path)) {
            $this->logger->error("[$envelope_libelle] Non trouvée en local ou sur le cloud");
            throw new RecoverableException("[$envelope_libelle] Non trouvée en local ou sur le cloud");
        }

        $this->logger->debug("[$envelope_libelle] Emplacement de l'archive :  $archive_path");

        $this->logger->debug("id_tdt : {$this->actes_appli_trigramme}, id_appli : {$this->actes_appli_quadrigramme}");

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
            $this->validatePades($archive_path, $tmp_dir);
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
     * @throws RecoverableException
     */
    private function validatePades($archive_filepath, $tmp_dir): void
    {
        $archive = new \Libriciel\LibActes\Archive();

        $archiveData = $archive->getArchiveDataFromTarball($archive_filepath, $tmp_dir);

        foreach ($archiveData->fichierXML as $fichierXML) {
            foreach ($fichierXML->getFileList() as $filekey) {
                if (is_array($fichierXML->$filekey)) {
                    foreach ($fichierXML->$filekey as $i => $filepath) {
                        $this->validatePADESOneFile($filepath);
                    }
                } else {
                    $this->validatePADESOneFile($fichierXML->$filekey);
                }
            }
        }
    }

    /**
     * @throws RecoverableException
     * @throws \Exception
     */
    private function validatePADESOneFile($filepath): void
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $filepath);
        finfo_close($finfo);
        if ($mime_type != 'application/pdf') {
            return;
        }

        $this->pdfValidator->check($filepath);

        $result = $this->padesValidator->validate($filepath);

        if ($result->connectionError) {
            throw new RecoverableException($result->message);
        }
        if (!$result->isValid) {
            $e = $result->exception;
            throw new Exception(
                'Problème sur ' . basename($filepath) . ' : ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    public function getMutexName($data): string
    {
        return sprintf("actes-transaction-%s", $data);
    }

    public function isDataValid($data): bool
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
    public function start(): void
    {
        // TODO: Implement start() method.
    }

    /**
     * @return void
     */
    public function end(): void
    {
        // TODO: Implement end() method.
    }
}
