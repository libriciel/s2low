<?php

namespace S2lowLegacy\Class\actes;

/* Archive au sens SEDA et pas au sens Actes ... */

use S2low\Services\CloudFileStorageInterface;
use S2low\Services\LocalFileResolver;
use S2lowLegacy\Class\PastellWrapperFactory;
use S2lowLegacy\Class\RecoverableException;
use S2lowLegacy\Class\S2lowLogger;
use S2lowLegacy\Class\TGZExtractor;
use S2lowLegacy\Class\TmpFolder;
use Exception;
use S2lowLegacy\Lib\UnrecoverableException;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\PastellPropertiesSQL;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ActesArchiveControler
{
    private PastellWrapperFactory $pastellWrapperFactory;

    private ActesTransactionsSQL $actesTransactionsSQL;

    private AuthoritySQL $authoritySQL;

    private PastellPropertiesSQL $pastellPropetiesSQL;

    private S2lowLogger $logger;

    private ActesEnvelopeSQL $actesEnvelopeSQL;
    private ActesTypePJSQL $actesTypePJSQL;
    private ActesCloudStorage $actesEnvelopeCloudStorage;

    /**
     * @throws \S2lowLegacy\Lib\UnrecoverableException
     */
    public function __construct(
        #[Autowire(service: 'app.localFileResolver.acte_enveloppe')]
        private readonly LocalFileResolver $acteEnveloppeFileResolver,
        #[Autowire(service: 'app.store.file.acte_enveloppe')]
        private readonly CloudFileStorageInterface $cloudFileStorage,
        PastellPropertiesSQL $pastellPropertiesSQL,
        S2lowLogger $logger,
        PastellWrapperFactory $pastellWrapperFactory,
        AuthoritySQL $authoritySQL,
        ActesTransactionsSQL $actesTransactionsSQL,
        ActesEnvelopeSQL $actesEnvelopeSQL,
        ActesTypePJSQL $actesTypePJSQL,
        ActesCloudStorage $cloudStorage,
    ) {
        $this->pastellWrapperFactory = $pastellWrapperFactory;
        $this->actesTransactionsSQL = $actesTransactionsSQL;
        $this->authoritySQL = $authoritySQL;
        $this->pastellPropetiesSQL = $pastellPropertiesSQL;
        $this->logger = $logger;
        $this->actesEnvelopeSQL = $actesEnvelopeSQL;
        $this->actesTypePJSQL = $actesTypePJSQL;
        $this->actesEnvelopeCloudStorage = $cloudStorage;
    }

    /**
     * @param int $limit
     * @return array
     */

    public function getAllTransactionIdToSend(int $limit = 0): array
    {
        if ($limit > 0) {
            return $this->actesTransactionsSQL->getTransactionToSendSAEWithLimit($limit);
        }
        return $this->actesTransactionsSQL->getTransactionToSendSAE();
    }


    /**
     * @param int $transaction_id
     * @throws Exception
     */
    public function sendArchive(int $transaction_id): void
    {

        $this->logger->info("Envoi de La transaction $transaction_id sur le SAE");
        if (! $this->isTransactionInGoodStatus($transaction_id)) {
            return;
        }

        $id_d = false;

        try {
            $this->sendArchiveThrow($transaction_id);
        } catch (RecoverableException $e) {
            $this->logger->error("Une erreur récupérable est survenue : " . $e->getMessage() . ". La transaction sera retentée.");
            if ($id_d) {
                $this->deletePastellDocument($transaction_id, $id_d);
                $this->logger->error("L'identifiant du document sur Pastell était : $id_d, le document a été supprimé sur Pastell");
            }
        } catch (FilesNotFoundInCloudException $e) {
            $message = "Documents indisponibles pour la transaction $transaction_id  : " . $e->getMessage();
            if ($id_d) {
                $this->deletePastellDocument($transaction_id, $id_d);
                $message .=  " - id_d=$id_d";
            }
            $this->logger->error($message);
            $this->actesTransactionsSQL->updateStatus(
                $transaction_id,
                ActesStatusSQL::STATUS_ERREUR_SAE_DOC_INDISPONIBLES,
                $message
            );
        } catch (Exception $e) {
            $message = "Impossible d'envoyer la transaction $transaction_id : " . $e->getMessage();
            if ($id_d) {
                $this->deletePastellDocument($transaction_id, $id_d);
                $message .=  " - id_d=$id_d";
            }
            $this->logger->error($message);
            $this->actesTransactionsSQL->updateStatus(
                $transaction_id,
                ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE,
                $message
            );
        }
    }

    /**
     * @param int $transaction_id
     * @throws RecoverableException
     * @throws Exception
     */
    public function sendArchiveThrow(int $transaction_id)
    {
        $tmpFolder = new TmpFolder();
        $tmp_folder = $tmpFolder->create();
        try {
            $transactionsInfo = $this->actesTransactionsSQL->getInfo($transaction_id);

            $this->authoritySQL->verifHasPastell($transactionsInfo[ActesTransactionsSQL::AUTHORITY_ID]);

            $actesFileForArchive = $this->prepareTransfert($transaction_id, $tmp_folder);

            $id_d = $this->createPastellDocument($transaction_id);
            $this->sendFilesToPastell($transaction_id, $id_d, $actesFileForArchive);
            $this->logger->info("La transaction $transaction_id a été envoyé sur le SAE (id_d pastell : $id_d)");

            $actesEnvelopeInfo = $this->actesEnvelopeSQL->getInfo($transactionsInfo['envelope_id']);
            $this->actesEnvelopeCloudStorage->deleteIfIsInCloud($actesEnvelopeInfo['id']);
        } finally {
            $tmpFolder->delete($tmp_folder);
        }
    }


    /**
     * @param $transaction_id
     * @param $tmp_folder
     * @return ActesFilesForSAE
     * @throws RecoverableException
     * @throws UnrecoverableException|\S2lowLegacy\Class\actes\FilesNotFoundInCloudException
     */
    private function prepareTransfert($transaction_id, $tmp_folder): ActesFilesForSAE
    {

        $actesFilesForSAE = new ActesFilesForSAE();

        $actesFile = $this->actesTransactionsSQL->getAllFile($transaction_id);

        $transactionsInfo = $this->actesTransactionsSQL->getInfo($transaction_id);

        $actesEnvelopeInfo = $this->actesEnvelopeSQL->getInfo($transactionsInfo['envelope_id']);
        $enveloppe_path = $this->acteEnveloppeFileResolver->getFullPath($actesEnvelopeInfo['id']);
        $this->cloudFileStorage->downloadFileFromCloud($actesEnvelopeInfo['id']);
        $isInCloud = $this->cloudFileStorage->fileExistOnCloud($actesEnvelopeInfo['id']);

        if (! $isInCloud) {
            throw new FilesNotFoundInCloudException(
                "Impossible de récupérer l'enveloppe {$actesEnvelopeInfo['file_path']}"
            );
        }

        $tgzExtractor = new TGZExtractor($tmp_folder);
        $tgzExtractor->extract($enveloppe_path, $actesFile[1]['filename']);

        $acte_filename = $actesFile[0]['filename'];

        $actesFilesForSAE->actes_filepath = $tmp_folder . "/" . $actesFile[1]['filename'];
        $actesFilesForSAE->actes_filename = $actesFile[1]['posted_filename'];
        $actesFilesForSAE->type_pj = $actesFile[1]['code_pj'];


        if ($actesFile[1]['signature']) {
            $signature_file_path = $tmp_folder . "/signature.pk7";
            file_put_contents($signature_file_path, $actesFile[1]['signature']);
            $actesFilesForSAE->signature_filepath = $signature_file_path;
        }

        $pdftampone = $tmp_folder . "/" . $actesFile[1]['filename'];
        $path_parts = pathinfo($pdftampone);
        if ($path_parts['extension'] == 'pdf' || $path_parts['extension'] == 'PDF') {
            $actesFilesForSAE->actes_tamponnees_filepath = $this->tamponerActe($tmp_folder, $actesFile[1]['filename'], $transactionsInfo['id']);
            $actesFilesForSAE->actes_tamponnees_filename = "acte_tampone." . $path_parts['extension'];
        }

        $date_postage = $this->actesTransactionsSQL->getStatusInfo($transactionsInfo['id'], 1);
        $actesFilesForSAE->date_postage = date("d/m/Y", strtotime($date_postage['date']));

        $objectInstancier = \S2lowLegacy\Lib\ObjectInstancierFactory::getObjetInstancier();
        $bordereauPdfGenerator = $objectInstancier->get(BordereauPdfGenerator::class);

        $actesFilesForSAE->bordereau_filepath = $tmp_folder . "/bordereau_acquit.pdf";

        $bordereauPdfGenerator->generate($transaction_id, $actesFilesForSAE->bordereau_filepath, false, "F");

        array_shift($actesFile);
        array_shift($actesFile);

        $actesFilesForSAE->annexe = [];
        foreach ($actesFile as $file) {
            $tgzExtractor->extract($enveloppe_path, $file['filename']);
            $actesFilesForSAE->annexe[] = ['filename' => $file['posted_filename'],'filepath' => $tmp_folder . '/' . $file['filename'],'type_pj' => $file['code_pj']];
        }

        $actesTransactionsStatusInfo = $this->actesTransactionsSQL->getStatusInfoWithFluxRetour($transaction_id, 4);

        if (is_null($actesTransactionsStatusInfo['flux_retour'])) {
            throw new UnrecoverableException("L'AR acte n'est pas disponible");
        }

        file_put_contents($tmp_folder . "/AR-{$acte_filename}", $actesTransactionsStatusInfo['flux_retour']);
        $actesFilesForSAE->aractes_filepath = $tmp_folder . "/AR-{$acte_filename}";


        $orig_acte_transaction_id = $transactionsInfo['id'];

        file_put_contents($tmp_folder . "/empty", "");

        $relatedTransaction = $this->actesTransactionsSQL->getRelatedTransaction($transaction_id);
        $echange_prefecture_type = array();
        $echange_prefecture = array();
        $echange_prefecture_ar = array();
        foreach ($relatedTransaction as $transaction) {
            $actesEnvelopeInfo = $this->actesEnvelopeSQL->getInfo($transaction['envelope_id']);
            $actesFile = $this->actesTransactionsSQL->getAllFile($transaction['id']);
            $file_to_send = $this->acteEnveloppeFileResolver->getFullPath($actesEnvelopeInfo['id']);
            $this->cloudFileStorage->downloadFileFromCloud($actesEnvelopeInfo['id']);

            if ($transaction['related_transaction_id'] == $orig_acte_transaction_id) {
                //Transaction aller
                $echange_prefecture_type[] = $transaction['type'] . 'A';
                $filename = $actesFile[0]['filename'];
                $posted_filename = $actesFile[0]['posted_filename'];
                array_shift($actesFile);
                $status_info =  $this->actesTransactionsSQL->getStatusInfoWithFluxRetour($transaction['id'], 8);
            } else {
                //Transaction retour
                $echange_prefecture_type[] = $transaction['type'] . 'R';

                $filename = $actesFile[1]['filename'];
                $posted_filename = $actesFile[1]['posted_filename'];
                array_shift($actesFile);
                array_shift($actesFile);
                $status_info =  $this->actesTransactionsSQL->getStatusInfoWithFluxRetour($transaction['id'], 11);
            }
            $tgzExtractor->extract($file_to_send, $filename);
            $echange_prefecture[] = array($tmp_folder . "/" . $filename,$posted_filename);

            if (isset($status_info['flux_retour']) && $status_info['flux_retour']) {
                $ar_name = "AR-" . $status_info['transaction_id'] . ".xml";
                file_put_contents($tmp_folder . "/$ar_name", $status_info['flux_retour']);
                $echange_prefecture_ar[] = array($tmp_folder . "/$ar_name",$ar_name);
            } else {
                $echange_prefecture_ar[] = array($tmp_folder . "/empty",'empty');
            }

            foreach ($actesFile as $annexe) {
                $tgzExtractor->extract($file_to_send . "/" . $annexe['filename'], $annexe['filename']);
                $echange_prefecture_type[] = $transaction['type'] . 'RB';
                $echange_prefecture[] = array($file_to_send . "/" . $annexe['filename'],$annexe['posted_filename']);
                $echange_prefecture_ar[] = array($tmp_folder . "/empty",'empty');
            }
        }

        $actesFilesForSAE->echange_prefecture = [$echange_prefecture_type,$echange_prefecture,$echange_prefecture_ar];
        $actesFilesForSAE->renameSameFilename();

        return $actesFilesForSAE;
    }

    /**
     * @param $transaction_id
     * @param $id_d
     * @param ActesFilesForSAE $actesFilesForSAE
     * @throws Exception
     */
    private function sendFilesToPastell($transaction_id, $id_d, ActesFilesForSAE $actesFilesForSAE)
    {
        $transactionsInfo = $this->actesTransactionsSQL->getInfo($transaction_id);

        $pastellProperties = $this->pastellPropetiesSQL->getPastellProperties($transactionsInfo['authority_id']);

        $pastell = $this->pastellWrapperFactory->getNewInstance($pastellProperties);

        $pastell->postActes(
            $id_d,
            $actesFilesForSAE->actes_filepath,
            $actesFilesForSAE->actes_filename
        );
        $this->logger->debug("postage de l'actes id_d : $id_d");

        if ($actesFilesForSAE->signature_filepath) {
            $pastell->postSignature($id_d, $actesFilesForSAE->signature_filepath);
        }

        if ($actesFilesForSAE->actes_tamponnees_filename && $actesFilesForSAE->actes_tamponnees_filepath) {
            $pastell->postFile(
                $id_d,
                "acte_tamponne",
                $actesFilesForSAE->actes_tamponnees_filepath,
                $actesFilesForSAE->actes_tamponnees_filename
            );
        }

        $pastell->setDatePostage($id_d, $actesFilesForSAE->date_postage);

        $pastell->postFile($id_d, "bordereau", $actesFilesForSAE->bordereau_filepath, "bordereau_acquittement.pdf");


        foreach ($actesFilesForSAE->annexe as $annexe) {
            $pastell->postAnnexe($id_d, $annexe['filepath'], $annexe['filename']);
        }

        $pastell->postARActes($id_d, $actesFilesForSAE->aractes_filepath);

        $pastell->postRelatedTransaction(
            $id_d,
            $actesFilesForSAE->echange_prefecture[0],
            $actesFilesForSAE->echange_prefecture[1],
            $actesFilesForSAE->echange_prefecture[2]
        );

        $default_type = $this->actesTypePJSQL->getDefaultType($transactionsInfo['nature_code']);

        $typologie = [$actesFilesForSAE->type_pj ?: $default_type];
        foreach ($actesFilesForSAE->annexe as $annexe) {
            $typologie[] = $annexe['type_pj'] ?: $default_type;
        }

        try {
            $pastell->modifExternalData($id_d, 'type_piece', ['type_pj' => $typologie]);
        } catch (Exception $e) {
            if (! ($e->getMessage() === "Erreur HTTP : Code 400")) {
                throw $e;
            }
            // Le champ n'est pas obligatoire. Si côté Pastell n'y a pas de Tdt dans le flux actes-generiques utilisé,
            // ou si la classification est absente, Pastell renvoie une erreur 400.
            $this->logger->warning("Le type des pièces jointes n'a pu être enregistré (Message Pastell :{$e->getMessage()})");
        }


        $result = $pastell->sendSAE($id_d, $pastellProperties->actes_action);
        if (! $result) {
            throw new UnrecoverableException($pastell->getLastError());
        }
        $this->actesTransactionsSQL->updateStatus(
            $transaction_id,
            ActesStatusSQL::STATUS_ENVOYE_AU_SAE,
            "Envoie de la transaction $transaction_id à Pastell"
        );
        $this->actesTransactionsSQL->setSAETransferIdentifier($transaction_id, $id_d);
    }

    /**
     * @param int $transaction_id
     * @return bool
     * @throws Exception
     */
    private function isTransactionInGoodStatus(int $transaction_id)
    {
        $transactionsInfo = $this->actesTransactionsSQL->getInfo($transaction_id);
        if ($transactionsInfo['last_status_id'] != ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE) {
            $this->logger->error(sprintf(
                "La transaction %d à envoyer au SAE n'est pas dans le bon status ! %d trouvé",
                $transaction_id,
                $transactionsInfo['last_status_id']
            ));
            return false;
        }
        return true;
    }

    /**
     * @param $id
     * @return bool
     * @throws Exception
     */
    private function createPastellDocument($id)
    {
        $transactionsInfo = $this->actesTransactionsSQL->getInfo($id);
        $pastellProperties = $this->pastellPropetiesSQL->getPastellProperties($transactionsInfo[ActesTransactionsSQL::AUTHORITY_ID]);
        $pastell = $this->pastellWrapperFactory->getNewInstance($pastellProperties);

        $id_d = $pastell->createActes($transactionsInfo);

        if (! $id_d) {
            throw new UnrecoverableException("Erreur pastell : " . $pastell->getLastError());
        }
        $this->logger->debug("Création du document sur Pastell id_d=$id_d");

        return $id_d;
    }

    public function tamponerActe($tmpfolder, $fileorig, $transactionId)
    {
        $pdftkise = $tmpfolder . "/tampon_" . $fileorig;

        $objectInstancier = \S2lowLegacy\Lib\ObjectInstancierFactory::getObjetInstancier();

        $acteTamponne = $objectInstancier->get(ActeTamponne::class);
        $tampon_content = $acteTamponne->tamponnerPDF($tmpfolder . "/" . $fileorig, $transactionId);

        file_put_contents($pdftkise, $tampon_content);
        return $pdftkise;
    }


    private function deletePastellDocument($transaction_id, $id_d)
    {
        $transactionsInfo = $this->actesTransactionsSQL->getInfo($transaction_id);
        $pastellProperties = $this->pastellPropetiesSQL->getPastellProperties($transactionsInfo['authority_id']);

        $pastell = $this->pastellWrapperFactory->getNewInstance($pastellProperties);
        try {
            $pastell->delete($id_d);
        } catch (Exception $e) {
            $this->logger->alert("Impossible de supprimer le document id_d sur {$pastellProperties->url} !");
            return false;
        }
        return true;
    }
}
