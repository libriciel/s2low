<?php

namespace S2lowLegacy\Class\helios;

use Exception;
use S2lowLegacy\Lib\UnrecoverableException;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use S2lowLegacy\Model\UserSQL;
use Monolog\Logger;

class HeliosPrepareEnvoiSAE
{
    private $lastError;

    private $heliosTransactionsSQL;
    private $userSQL;
    private $authoritySQL;
    private $logger;

    public function __construct(
        Logger $logger,
        UserSQL $userSQL,
        AuthoritySQL $authoritySQL,
        HeliosTransactionsSQL $heliosTransactionsSQL
    ) {

        $this->heliosTransactionsSQL = $heliosTransactionsSQL;
        $this->authoritySQL = $authoritySQL;
        $this->logger = $logger;
        $this->userSQL = $userSQL;
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    public function setArchiveEnAttenteEnvoiSEA(int $user_id, int $transaction_id): bool
    {
        try {
            $transaction_info = $this->getTransactionInfo($transaction_id);

            $this->isAllowToSendArchive($user_id, $transaction_info);

            if (!in_array($transaction_info['last_status_id'], array(8,4, 6, 11,20))) {
                throw new UnrecoverableException(
                    "Impossible d'archiver une transaction qui n'est pas en état « Information disponible », « acquitté » ou « refusé »."
                );
            }
            $this->authoritySQL->verifHasPastell($transaction_info[HeliosTransactionsSQL::AUTHORITY_ID]);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            $this->lastError = $e->getMessage();
            return false;
        }

        $this->heliosTransactionsSQL->updateStatus(
            $transaction_id,
            HeliosStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE,
            "En attente de l'envoi au SAE"
        );
        $this->logger->info("La transaction $transaction_id passe en attente de transmission au SAE");
        return true;
    }

    /**
     * @param $transaction_id
     * @return array
     * @throws UnrecoverableException
     */
    private function getTransactionInfo($transaction_id): array
    {
        $transaction_info = $this->heliosTransactionsSQL->getInfo($transaction_id);
        if (!$transaction_info) {
            throw new UnrecoverableException("La transaction $transaction_id n'existe pas");
        }
        return $transaction_info;
    }

    /**
     * @param $user_id
     * @param $transactionsInfo
     * @return bool
     * @throws Exception
     */
    private function isAllowToSendArchive($user_id, $transactionsInfo)
    {
        if ($transactionsInfo['user_id'] == $user_id) {
            return true;
        }
        $user_info = $this->userSQL->getInfo($user_id);

        if ($user_info['role'] == 'SADM') {
            return true;
        }
        if ($user_info['role'] != 'ADM') {
            throw new UnrecoverableException("Accès interdit");
        }
        if ($user_info[UserSQL::AUTHORITY_ID] == $transactionsInfo[HeliosTransactionsSQL::AUTHORITY_ID]) {
            return true;
        }

        throw new UnrecoverableException("Accès interdit");
    }

    public function setArchiveEnAttenteEnvoiSEAManuellement(
        int $authority_id,
        $nb_days = HeliosPrepareSaeWorker::NB_DAYS_ARCHIVE_AFTER
    ) {
        $this->logger->info("Début du script");
        $transaction_id_list = $this->heliosTransactionsSQL->getTransactionToPrepareToSAE(
            $nb_days,
            $authority_id,
            false,
            [
                HeliosStatusSQL::INFORMATION_DISPONIBLE,
                HeliosStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE
            ]
        );

        $this->logger->info(sprintf(
            "%d transaction(s) vont être traité(s)",
            count($transaction_id_list)
        ));

        foreach ($transaction_id_list as $transaction_id) {
            $transaction_info = $this->heliosTransactionsSQL->getInfo($transaction_id);
            $this->logger->info("Traitement de $transaction_id - {$transaction_info['id']}");
            $this->setArchiveEnAttenteEnvoiSEA($transaction_info['user_id'], $transaction_id);
        }
        $this->logger->info("Fin du script");
    }
}
