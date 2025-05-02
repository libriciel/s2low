<?php

namespace S2lowLegacy\Class\actes;

use Psr\Log\LoggerInterface;
use S2lowLegacy\Class\User;
use S2lowLegacy\Class\WorkerScript;
use Exception;
use S2lowLegacy\Lib\UnrecoverableException;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\UserSQL;

class ActesPrepareEnvoiSAE
{
    private $actesTransactionsSQL;
    private $logger;
    private $authoritySQL;
    private $workerScript;
    private $userSQL;

    private $lastError;

    public function __construct(
        ActesTransactionsSQL $actesTransactionsSQL,
        LoggerInterface $logger,
        AuthoritySQL $authoritySQL,
        WorkerScript $workerScript,
        UserSQL $userSQL
    ) {
        $this->actesTransactionsSQL = $actesTransactionsSQL;
        $this->logger = $logger;
        $this->authoritySQL = $authoritySQL;
        $this->workerScript = $workerScript;
        $this->userSQL = $userSQL;
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * @param $user_id
     * @param $transaction_id
     * @param bool $put_in_job_queue
     * @return array|bool|mixed
     */
    public function setArchiveEnAttenteEnvoiSEA($user_id, $transaction_id, $put_in_job_queue = true)
    {
        try {
            $transactionsInfo = $this->actesTransactionsSQL->getInfo($transaction_id);
            $this->logger->info("Préparation de l'envoie au SAE pour l'actes $transaction_id - {$transactionsInfo['unique_id']} : en cours");

            $user = new User($user_id);
            $user->init();
            $this->isAllowToSendArchive($user_id, $transactionsInfo);

            if (!in_array($transactionsInfo['last_status_id'], array(4, 5, 14, 20)) || $transactionsInfo['type'] != 1) {
                throw new UnrecoverableException("Impossible d'archiver une transaction qui n'est pas en état « Acquittement reçu » ou « Validé ».");
            }
            $this->authoritySQL->verifHasPastell($transactionsInfo[ActesTransactionsSQL::AUTHORITY_ID]);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            $this->lastError = $e->getMessage();
            return false;
        }
        $actes_transaction_workflow_id = $this->actesTransactionsSQL->updateStatus(
            $transaction_id,
            ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE,
            "En attente de l'envoi au SAE"
        );

        if ($put_in_job_queue) {
            $this->workerScript->putJobByClassName(
                ActesEnvoiSaeWorker::class,
                $transaction_id
            );
        }
        $this->logger->info("Préparation de l'envoi SAE pour l'actes $transaction_id - {$transactionsInfo['unique_id']} : OK");
        return $actes_transaction_workflow_id;
    }

    /**
     * @param $user_id
     * @param $transactionsInfo
     * @return bool
     * @throws UnrecoverableException
     */
    private function isAllowToSendArchive($user_id, $transactionsInfo)
    {
        if (! $transactionsInfo) {
            throw new UnrecoverableException("Impossible de d'envoyer la transaction");
        }
        if ($transactionsInfo['user_id'] == $user_id) {
            return true;
        }
        $transactionsInfo[ActesTransactionsSQL::AUTHORITY_ID];
        $user_info = $this->userSQL->getInfo($user_id);

        if ($user_info['role'] == 'SADM') {
            return true;
        }

        if ($user_info['role'] != 'ADM') {
            throw new UnrecoverableException("Accès interdit");
        }

        if ($user_info[ActesTransactionsSQL::AUTHORITY_ID] == $transactionsInfo[ActesTransactionsSQL::AUTHORITY_ID]) {
            return true;
        }

        throw new UnrecoverableException("Accès interdit");
    }
}
