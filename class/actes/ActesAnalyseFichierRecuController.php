<?php

namespace S2lowLegacy\Class\actes;

use Exception;
use finfo;
use Libriciel\LibActes\ArchiveData;
use Libriciel\LibActes\FichierXML\MessageMetieAnomalieActe;
use Libriciel\LibActes\FichierXML\MessageMetierARActes;
use Libriciel\LibActes\FichierXML\MessageMetierARAnnulation;
use Libriciel\LibActes\FichierXML\MessageMetierARPieceComplementaire;
use Libriciel\LibActes\FichierXML\MessageMetierARReponseRejetLettreObservations;
use Libriciel\LibActes\FichierXML\MessageMetierCourrierSimple;
use Libriciel\LibActes\FichierXML\MessageMetierDefereTA;
use Libriciel\LibActes\FichierXML\MessageMetierDemandePieceComplementaire;
use Libriciel\LibActes\FichierXML\MessageMetierLettreObservations;
use Libriciel\LibActes\FichierXML\MessageMetierReponseClassificationSansChangement;
use Libriciel\LibActes\FichierXML\MessageMetierRetourClassification;
use Libriciel\LibActes\Utils\XSDValidationException;
use Psr\Log\LoggerInterface;
use S2lowLegacy\Class\TmpFolder;
use S2lowLegacy\Lib\SigTermHandler;
use UnexpectedValueException;

class ActesAnalyseFichierRecuController
{
    private $s2lowLogger;
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
        LoggerInterface $s2lowLogger,
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
        $this->s2lowLogger = $s2lowLogger;
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

    /**
     * @return bool
     * @throws Exception
     */
    public function analyseAll()
    {
        $this->s2lowLogger->info("Début du script");
        $this->s2lowLogger->info("Analyse du répertoire : {$this->actes_response_tmp_local_path}");

        $file_list = $this->getAllDirectory();

        if (!$file_list) {
            $this->s2lowLogger->info("Aucun répertoire à analyser");
            return true;
        }
        $sigtermHandler = SigTermHandler::getInstance();
        foreach ($file_list as $file) {
            $this->analyseOneFileMoveIfError($file);
            if ($sigtermHandler->isSigtermCalled()) {
                break;
            }
        }

        $this->s2lowLogger->info("Fin du script");
        return true;
    }

    public function getAllDirectory()
    {
        $file_list = @ scandir($this->actes_response_tmp_local_path);

        if ($file_list === false) {
            $message = "Erreur lors de la lecture du répertoire  $this->actes_response_tmp_local_path";
            $this->s2lowLogger->error($message);
            throw new Exception($message);
        }
        $file_list = array_diff($file_list, array('..', '.'));
        $this->s2lowLogger->info("Traitement de " . count($file_list) . " répertoire trouvés");
        return $file_list;
    }


    public function analyseOneFileMoveIfError($file)
    {
        $rep_path = $this->actes_response_tmp_local_path . "/" . $file;
        try {
            $this->analyseOneFile($rep_path);
            $tmpDir = new TmpFolder();
            $this->s2lowLogger->info("Suppression du répertoire $rep_path");
            $tmpDir->delete($rep_path);
        } catch (Exception $e) {
            $this->s2lowLogger->error("Echec du traitement de $rep_path : " . $e->getMessage());
            $this->s2lowLogger->error("Déplacement du répertoire $file vers {$this->actes_response_error_path}");
            rename($rep_path, $this->actes_response_error_path . "/" . $file);
        }
    }

    private function isMulticanalResponse($rep_path)
    {
        $message_body = $rep_path . "/message_body.html";
        if (! file_exists($message_body)) {
            return false;
        }
        $expected_content = "Notre service de contrôle de légalité a identifié qu'il s'agit d'un acte dont la transmission est effectuée en multi canal.";
        if (
            ! preg_match(
                "#$expected_content#",
                file_get_contents($message_body)
            )
        ) {
            return false;
        }
        return true;
    }

    /**
     * @param $rep_path
     * @throws Exception
     */
    public function analyseOneFile($rep_path)
    {
        $this->s2lowLogger->info("Traitement de $rep_path");

        $archive = new \Libriciel\LibActes\Archive();

        try {
            $archiveData = $archive->getArchiveDataFromFolder($rep_path);
        } catch (XSDValidationException $e) {
            $message = $e->getMessage() . ' ' . implode(";", $e->displayValidationErrors());
            $this->s2lowLogger->error($message);
            throw new Exception($message);
        } catch (Exception $e) {
            if ($this->isMulticanalResponse($rep_path)) {
                $this->s2lowLogger->info("Message de réponse à un multicanal");
                return;
            }
            throw new Exception($e->getMessage());
        }

        if ($archiveData->is_ano) {
            $this->traitementEnveloppeAnomalie($archiveData);
            return;
        }

        foreach ($archiveData->fichierXML as $fichierXML) {
            $code_message = $fichierXML->getCodeMessage();
            $this->s2lowLogger->info("Code message : $code_message");
            if ($code_message == MessageMetierARActes::CODE_MESSAGE) {
                /** @var MessageMetierARActes $fichierXML */
                $this->traitementARActe($fichierXML);
            } elseif ($code_message == MessageMetierReponseClassificationSansChangement::CODE_MESSAGE) {
                /** @var MessageMetierReponseClassificationSansChangement $fichierXML */
                $this->traitementRetourClassificationSansChangement($fichierXML);
            } elseif ($code_message == MessageMetierRetourClassification::CODE_MESSAGE) {
                /** @var MessageMetierRetourClassification $fichierXML */
                $this->traitementRetourClassification($fichierXML);
            } elseif ($code_message == MessageMetierARAnnulation::CODE_MESSAGE) {
                /** @var MessageMetierARAnnulation $fichierXML */
                $this->traitementARAnnulation($fichierXML);
            } elseif (
                in_array($code_message, array
                (
                    MessageMetierCourrierSimple::CODE_MESSAGE,
                    MessageMetierDemandePieceComplementaire::CODE_MESSAGE,
                    MessageMetierLettreObservations::CODE_MESSAGE,
                    MessageMetierDefereTA::CODE_MESSAGE,
                ))
            ) {
                $this->traitementDocumentRecu($archiveData, $rep_path);
            } elseif ($code_message == MessageMetieAnomalieActe::CODE_MESSAGE) {
                /** @var MessageMetieAnomalieActe $fichierXML */
                $this->traitementAnomalie($fichierXML);
            } elseif ($code_message == MessageMetierARPieceComplementaire::CODE_MESSAGE) {
                /** @var MessageMetierARPieceComplementaire $fichierXML */
                $this->traitementARPC($fichierXML);
            } elseif ($code_message == MessageMetierARReponseRejetLettreObservations::CODE_MESSAGE) {
                /** @var MessageMetierARReponseRejetLettreObservations $fichierXML */
                $this->traitementARReponseLO($fichierXML);
            } else {
                throw new Exception("Code message $code_message non géré");
            }
        }
    }

    /**
     * @param ArchiveData $archiveData
     * @throws Exception
     */
    private function traitementEnveloppeAnomalie(ArchiveData $archiveData)
    {
        $enveloppe_anomalie_name = basename($archiveData->enveloppe_path);
        $this->s2lowLogger->info("Anomalie trouvée pour l'enveloppe $enveloppe_anomalie_name");
        $envelope_id = $this->actesEnvelopeSQL->findByAnomalieEnveloppeName($enveloppe_anomalie_name);
        if (! $envelope_id) {
            throw new Exception("L'enveloppe d'anomalie $enveloppe_anomalie_name ne correspond à aucune enveloppe de la base");
        }
        $this->s2lowLogger->info("Enveloppe $envelope_id trouvé pour l'anomalie $enveloppe_anomalie_name");

        $transaction_ids = $this->actesTransactionsSQL->getIdByEnvelopeId($envelope_id);

        $actesXML = new \Libriciel\LibActes\ActesXML();
        /** @var \Libriciel\LibActes\FichierXML\EnveloppeAnomalie $anomalieEnveloppe */
        $anomalieEnveloppe = $actesXML->getDataFromXML(file_get_contents($archiveData->enveloppe_path));

        $detail_erreur = $anomalieEnveloppe->detail_erreur;

        $message = "Enveloppe rejetée par le {$this->actes_ministere_acronyme} ({$anomalieEnveloppe->nature_erreur} : $detail_erreur)";
        $xml = mb_convert_encoding(file_get_contents($archiveData->enveloppe_path), "UTF-8", "ISO-8859-1"); // FIX conversion UTF-8
                                                    //Est-on sûr que l'acte est toujours en ISO-8859-1 ??
        $this->updateStatus(
            $transaction_ids,
            ActesStatusSQL::STATUS_EN_ERREUR,
            $message,
            $xml
        );
    }


    private function generateZip($rep_path, ArchiveData $archiveData, $archive_folder)
    {
        $archiveFilename = new \Libriciel\LibActes\ArchiveFilename();
        $targz_filename = $archiveFilename->getFilename($archiveData->id_tdt, basename($archiveData->enveloppe_path));

        $tar_filename = mb_substr($targz_filename, 0, -3);

        $final_destination = $archive_folder . "/" . $tar_filename . ".gz";

        if (file_exists($final_destination)) {
            unlink($final_destination);
        }

        $pharData = new \PharData($archive_folder . "/" . $tar_filename);
        foreach (glob("$rep_path/*") as $file) {
            $pharData->addFile($file, basename($file));
        }
        $pharData->compress(\Phar::GZ);

        return $final_destination;
    }

    /**
     * @param ArchiveData $archiveData
     * @throws Exception
     */
    private function traitementDocumentRecu(ArchiveData $archiveData, $rep_path)
    {
        $fichierXML = $archiveData->fichierXML;
        $fichierXML = $fichierXML[0];

        $transaction_id = $this->getBySirenAndNumeroInterne($fichierXML->siren, $fichierXML->numero_interne);

        $archive_folder = $this->actes_files_upload_root . "/{$fichierXML->siren}/{$fichierXML->numero_interne}";

        if (is_file($archive_folder)) {
                throw new UnexpectedValueException(
                    "Impossible de créer $archive_folder : un fichier de ce nom existe déjà"
                );
        }
        if (!is_dir($archive_folder)) {
            mkdir($archive_folder, 0755, true);
        }

        $archiveData->id_tdt = ACTES_APPLI_TRIGRAMME;

        $archive_path = $this->generateZip($rep_path, $archiveData, $archive_folder);


        $envelope_path = mb_substr($archive_path, mb_strlen($this->actes_files_upload_root));
        $envelope_size = filesize($archive_path);

        $this->s2lowLogger->info("Archive enregistré dans $archive_path");

        $transaction_info = $this->actesTransactionsSQL->getInfo($transaction_id);

        $related_envelope_id = $this->actesEnvelopeSQL->createRelatedEnveloppe(
            $transaction_info['envelope_id'],
            $envelope_path,
            $envelope_size
        );
        $this->s2lowLogger->info("Création de l'enveloppe $related_envelope_id");

        //HORRIBLE HACK
        if (isset($fichierXML->date_courrier_pref)) {
            $date_decision = $fichierXML->date_courrier_pref;
        } elseif (isset($fichierXML->date_lettre_observation)) {
            $date_decision = $fichierXML->date_lettre_observation;
        } elseif (isset($fichierXML->date_depot)) {
            $date_decision =  $fichierXML->date_depot;
        } else {
            $date_decision = "";
        }

        $related_transaction_id = $this->actesTransactionsSQL->createRelatedTransaction(
            $related_envelope_id,
            mb_substr($fichierXML->getCodeMessage(), 0, 1),
            $date_decision,
            $transaction_id
        );

        $this->s2lowLogger->info("Création de la transaction $related_transaction_id");



        foreach ($fichierXML->getFileList() as $item) {
            if (is_array($fichierXML->$item)) {
                foreach ($fichierXML->$item as $sub_item) {
                    $this->addFile($related_envelope_id, $related_transaction_id, $sub_item);
                }
            } else {
                $this->addFile($related_envelope_id, $related_transaction_id, $fichierXML->$item);
            }
        }

        if (
            in_array(
                $fichierXML->getCodeMessage(),
                array(MessageMetierCourrierSimple::CODE_MESSAGE,
                MessageMetierDefereTA::CODE_MESSAGE)
            )
        ) {
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

    private function addFile($related_envelope_id, $related_transaction_id, $filepath)
    {
        $finfo = new finfo();
        $filename = basename($filepath);
        $filesize = filesize($filepath);
        $content_type = $finfo->file($filepath, FILEINFO_MIME_TYPE);
        $file_id = $this->actesIncludedFileSQL->addIncludedFile(
            $related_envelope_id,
            $related_transaction_id,
            $content_type,
            $filesize,
            $filename
        );
        $this->s2lowLogger->info("Attachement du fichier $file_id");
    }

    /**
     * @param MessageMetierARActes $fichierXML
     * @throws Exception
     */
    private function traitementARActe(MessageMetierARActes $fichierXML)
    {
        $this->s2lowLogger->info("AR Actes trouvé pour l'acte : " . $fichierXML->id_actes);

        $transaction_id = $this->getBySirenAndNumeroInterne($fichierXML->siren, $fichierXML->numero_interne);

        $this->actesTransactionsSQL->setUniqueID($transaction_id, $fichierXML->id_actes);

        $this->s2lowLogger->info("$fichierXML->id_actes -> transaction_id = $transaction_id");

        $message = "Reçu par le {$this->actes_ministere_acronyme} le " . $fichierXML->date_reception;

        $xml = file_get_contents($fichierXML->file_path);

        $this->updateStatus(
            $transaction_id,
            ActesStatusSQL::STATUS_ACQUITTEMENT_RECU,
            $message,
            $xml
        );
    }

    /**
     * @param MessageMetierARPieceComplementaire $fichierXML
     * @throws Exception
     */
    private function traitementARPC(MessageMetierARPieceComplementaire $fichierXML)
    {
        $this->s2lowLogger->info("AR Actes trouvé pour l'envoi de piece complementaire : " . $fichierXML->id_actes);

        $transaction_id = $this->getBySirenAndNumeroInterne($fichierXML->siren, $fichierXML->numero_interne, TypeTransaction::DemandePieceComplementaire, true);

        $this->s2lowLogger->info("$fichierXML->id_actes -> transaction_id = $transaction_id");
        $message = "Reçu par le {$this->actes_ministere_acronyme} le " . $fichierXML->date_reception;

        $xml = file_get_contents($fichierXML->file_path);

        $this->updateStatus(
            $transaction_id,
            ActesStatusSQL::STATUS_ACQUITTEMENT_RECU,
            $message,
            $xml
        );
    }

    /**
     * @param MessageMetierARReponseRejetLettreObservations $fichierXML
     * @throws Exception
     */
    private function traitementARReponseLO(MessageMetierARReponseRejetLettreObservations $fichierXML)
    {
        $this->s2lowLogger->info("AR Actes trouvé pour l'envoi d'une réponse ou d'un refus à une lettre d'observation : " . $fichierXML->id_actes);

        $transaction_id = $this->getBySirenAndNumeroInterne($fichierXML->siren, $fichierXML->numero_interne, TypeTransaction::LettreDObservation, true);

        $this->s2lowLogger->info("$fichierXML->id_actes -> transaction_id = $transaction_id");
        $message = "Reçu par le {$this->actes_ministere_acronyme} le " . $fichierXML->date_reception;

        $xml = file_get_contents($fichierXML->file_path);

        $this->updateStatus(
            $transaction_id,
            ActesStatusSQL::STATUS_ACQUITTEMENT_RECU,
            $message,
            $xml
        );
    }

    /**
     * @param MessageMetieAnomalieActe $fichierXML
     * @throws Exception
     */
    private function traitementAnomalie(MessageMetieAnomalieActe $fichierXML)
    {
        $this->s2lowLogger->info("Anomalie trouvé pour l'acte : " . $fichierXML->numero_interne);
        $transaction_id = $this->getBySirenAndNumeroInterne($fichierXML->siren, $fichierXML->numero_interne);
        $message = "Anomalie signalee par le {$this->actes_ministere_acronyme} : " . $fichierXML->nature_anomalie . " - " . $fichierXML->detail_anomalie;

        $info_transaction = $this->actesTransactionsSQL->getInfo($transaction_id);
        if ($info_transaction['last_status_id'] != ActesStatusSQL::STATUS_TRANSMIS) {
            $this->s2lowLogger->info("$message");
            $exception_message  = "Message d'anomalie reçu alors que le status de l'acte n'est plus transmis ({$info_transaction['last_status_id']} trouvé)";
            $this->s2lowLogger->error($exception_message);
            throw new Exception($exception_message);
        }

        $xml = file_get_contents($fichierXML->file_path);
        $this->updateStatus(
            $transaction_id,
            ActesStatusSQL::STATUS_EN_ERREUR,
            $message,
            $xml
        );
    }

    /**
     * @param MessageMetierRetourClassification $fichierXML
     * @throws Exception
     */
    private function traitementRetourClassification(MessageMetierRetourClassification $fichierXML)
    {
        $transaction_id = $this->actesTransactionsSQL->getLastDemandeClassificationTransmis($fichierXML->siren);
        if (! $transaction_id) {
            throw new Exception("Aucune demande de classification transmise trouvée pour le siren {$fichierXML->siren}");
        }
        $this->actesUpdateClassificationSQL->updateClassification($fichierXML->siren, file_get_contents($fichierXML->file_path));
        $message = "Mise à jour de la classification (date de classification: {$fichierXML->date_classification})";
        $xml = file_get_contents($fichierXML->file_path);

        $this->updateStatus(
            $transaction_id,
            ActesStatusSQL::STATUS_ACQUITTEMENT_RECU,
            $message,
            $xml
        );
    }

    /**
     * @param MessageMetierReponseClassificationSansChangement $fichierXML
     * @throws Exception
     */
    private function traitementRetourClassificationSansChangement(MessageMetierReponseClassificationSansChangement $fichierXML)
    {
        $this->s2lowLogger->info("Classification sans changement reçu");
        $transaction_id = $this->actesTransactionsSQL->getLastDemandeClassificationTransmis($fichierXML->siren);
        if (! $transaction_id) {
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

    /**
     * @param MessageMetierARAnnulation $fichierXML
     * @throws Exception
     */
    public function traitementARAnnulation(MessageMetierARAnnulation $fichierXML)
    {
        $this->s2lowLogger->info("Annulation trouvée pour l'Acte : " . $fichierXML->id_actes);

        $transaction_id = $this->getBySirenAndNumeroInterne($fichierXML->siren, $fichierXML->numero_interne);

        $this->s2lowLogger->info("{$fichierXML->id_actes} -> transaction_id = $transaction_id");
        $message = "Annulation reçue par le {$this->actes_ministere_acronyme} le " . $fichierXML->date_reception;

        $xml = file_get_contents($fichierXML->file_path);

        $this->updateStatus(
            $transaction_id,
            ActesStatusSQL::STATUS_ANNULER,
            $message,
            $xml
        );
        $transaction_annulation_id = $this->getBySirenAndNumeroInterne($fichierXML->siren, $fichierXML->numero_interne, TypeTransaction::Annulation);

        $this->updateStatus(
            $transaction_annulation_id,
            ActesStatusSQL::STATUS_ACQUITTEMENT_RECU,
            $message,
            $xml
        );
    }

    private function updateStatus(array | int $transaction_ids, int $status_id, string $message, $xml = false)
    {
        if (! is_array($transaction_ids)) {
            $transaction_ids = array($transaction_ids);
        }
        $this->s2lowLogger->info($message);
        $this->actesScriptHelper->updateStatusAndLog(
            $transaction_ids,
            $status_id,
            $message,
            $xml
        );
    }

    /**
     * @param $siren
     * @param $numeroInterne
     * @param int $type
     * @param bool $type_reponse_not_null
     * @return array|bool|mixed
     * @throws Exception
     */
    private function getBySirenAndNumeroInterne($siren, $numeroInterne, TypeTransaction $type = TypeTransaction::TransmissionActe, $type_reponse_not_null = false)
    {
        $transaction_id = $this->actesTransactionsSQL->getBySirenAndNumeroInterne($siren, $numeroInterne, $type, $type_reponse_not_null);
        if (! $transaction_id) {
            throw new Exception(
                "Aucune transation trouver pour le couple SIREN $siren - numéro interne $numeroInterne"
            );
        }
        $this->s2lowLogger->info("Transaction de type $type->value trouvé avec le SIREN $siren et le numéro interne $numeroInterne : $transaction_id ");
        return $transaction_id;
    }
}
