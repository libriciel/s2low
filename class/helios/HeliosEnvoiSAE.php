<?php

namespace S2lowLegacy\Class\helios;

use Psr\Log\LoggerInterface;
use S2lowLegacy\Class\actes\FilesNotFoundInCloudException;
use S2lowLegacy\Class\PastellWrapperFactory;
use Exception;
use S2lowLegacy\Lib\SigTermHandler;
use S2lowLegacy\Lib\UnrecoverableException;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use S2lowLegacy\Model\PastellPropertiesSQL;

class HeliosEnvoiSAE
{
    private $heliosTransactionsSQL;
    private $pastellWrapperFactory;
    private $pesAllerRetriever;
    private $logger;
    private $authoritySQL;
    private $pastellPropertiesSQL;

    public function __construct(
        PesAllerRetriever $pesAllerRetriever,
        PastellWrapperFactory $pastellWrapperFactory,
        LoggerInterface $logger,
        AuthoritySQL $authoritySQL,
        HeliosTransactionsSQL $heliosTransactionsSQL,
        PastellPropertiesSQL $pastellPropertiesSQL,
        private readonly PESAcquitCloudStorage $pesAcquitCloudStorage,
        private readonly PESAllerCloudStorage $pesAllerCloudStorage
    ) {
        $this->heliosTransactionsSQL = $heliosTransactionsSQL;
        $this->authoritySQL = $authoritySQL;
        $this->pastellWrapperFactory = $pastellWrapperFactory;
        $this->pesAllerRetriever = $pesAllerRetriever;
        $this->logger = $logger;
        $this->pastellPropertiesSQL = $pastellPropertiesSQL;
    }

    public function sendAllArchive($authority_id = 0)
    {
        $sigtermHandler = SigTermHandler::getInstance();
        $this->logger->info("Début de l'envoi");
        $info_list = $this->heliosTransactionsSQL->getIdsByStatus(
            HeliosStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE,
            $authority_id
        );
        $this->logger->info(count($info_list) . " transactions à envoyer...");
        foreach ($info_list as $transaction_id) {
            $this->logger->info("Envoi de la transaction $transaction_id.");
            $this->sendArchive($transaction_id);
            if ($sigtermHandler->isSigtermCalled()) {
                break;
            }
        }
        $this->logger->info("Fin de l'envoi");
    }

    public function sendArchive($id)
    {
        try {
            $this->sendArchiveThrow($id);
            $this->logger->info("La transaction $id a été envoyée à Pastell");
        } catch (FilesNotFoundInCloudException $e) {
            $message = "Documents indisponibles pour la transaction $id  : " . $e->getMessage();
            $this->heliosTransactionsSQL->updateStatus(
                $id,
                HeliosStatusSQL::STATUS_ERREUR_SAE_DOC_INDISPONIBLES,
                $message
            );
            $this->logger->error($message);
            return false;
        } catch (Exception $e) {
            $message = "La transaction $id n'a pas pu être envoyée sur Pastell : " . $e->getMessage();
            $this->heliosTransactionsSQL->updateStatus(
                $id,
                HeliosStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE,
                $message
            );
            $this->logger->error($message);
            return false;
        }
        return true;
    }

    /**
     * @param int $transaction_id
     * @return bool
     * @throws Exception
     */
    public function sendArchiveThrow(int $transaction_id): bool
    {
            $this->logger->info("Début du traitement de la transaction $transaction_id");
            $transactionsInfo = $this->heliosTransactionsSQL->getInfo($transaction_id);

            $this->authoritySQL->verifHasPastell($transactionsInfo[HeliosTransactionsSQL::AUTHORITY_ID]);

            $this->logger->info("Début de la récupération des fichiers de la transaction $transaction_id");
            $pes_aller_filepath = $this->pesAllerRetriever->getPath($transactionsInfo['sha1']);

        if (! $pes_aller_filepath) {
            throw new FilesNotFoundInCloudException("Impossible de récupérer le PES ALLER {$transactionsInfo['sha1']}");
        }

            $pes_acquit_filepath = $this->pesAcquitCloudStorage->getPath($transaction_id);

            $pastellProperties = $this->pastellPropertiesSQL->getPastellProperties($transactionsInfo[HeliosTransactionsSQL::AUTHORITY_ID]);
            $this->logger->info("Début du transfert vers $pastellProperties->url de la transaction $transaction_id");

            $pastell = $this->pastellWrapperFactory->getNewInstance($pastellProperties);

            $id_d = $pastell->createHelios($transactionsInfo);

        if (!$id_d) {
            throw new UnrecoverableException($pastell->getLastError());
        }

        try {
            $pastell->postFile($id_d, 'fichier_pes', $pes_aller_filepath, $transactionsInfo['complete_name']);
            $pastell->postFile($id_d, 'fichier_reponse', $pes_acquit_filepath, $transactionsInfo['acquit_filename']);

            $result = $pastell->sendSAE($id_d, $pastellProperties->helios_action);

            if (!$result) {
                throw new UnrecoverableException(
                    "Impossible d'envoyer la transaction sur as@lae : " . $pastell->getLastError()
                );
            }
            $this->heliosTransactionsSQL->updateStatus(
                $transaction_id,
                HeliosStatusSQL::ENVOYER_AU_SAE,
                "Envoie de la transaction $transaction_id à Pastell"
            );
            $this->heliosTransactionsSQL->setSAETransferIdentifier($transaction_id, $id_d);
        } catch (Exception $exception) {
            $message = '[' . get_class($exception) . '] ' . $exception->getMessage();
            try {
                $pastell->delete($id_d);
            } catch (Exception $exception2) {
                $message .= " et erreur lors de la suppression de $id_d\[" . get_class($exception2) . '] ' . $exception2->getMessage();
            }
            throw new Exception($message);
        }

        $this->pesAllerCloudStorage->deleteIfIsInCloud($transaction_id);


        return true;
    }
}
