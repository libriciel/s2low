<?php

namespace S2lowLegacy\Class\helios;

use S2lowLegacy\Class\IWorker;
use S2lowLegacy\Class\S2lowLogger;
use S2lowLegacy\Model\HeliosTransactionsSQL;

class HeliosPrepareSaeWorker implements IWorker
{
    public const QUEUE_NAME = 'helios-prepare-sae';

    public const NB_DAYS_ARCHIVE_AFTER = 15;

    private $heliosPrepareEnvoiSAE;
    private $s2lowLogger;
    private $heliosTransactionsSQL;

    public function __construct(
        HeliosTransactionsSQL $heliosTransactionsSQL,
        S2lowLogger $s2lowLogger,
        HeliosPrepareEnvoiSAE $heliosPrepareEnvoiSAE
    ) {
        $this->heliosTransactionsSQL = $heliosTransactionsSQL;
        $this->s2lowLogger = $s2lowLogger;
        $this->heliosPrepareEnvoiSAE = $heliosPrepareEnvoiSAE;
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
        return $this->heliosTransactionsSQL->getTransactionToPrepareToSAE(self::NB_DAYS_ARCHIVE_AFTER);
    }

    /**
     * @param $transaction_id
     * @return void
     */
    public function work($transaction_id)
    {
        $transaction_info = $this->heliosTransactionsSQL->getInfo($transaction_id);
        $this->heliosPrepareEnvoiSAE->setArchiveEnAttenteEnvoiSEA(
            $transaction_info['user_id'],
            $transaction_id
        );
    }

    public function getMutexName($data)
    {
        return sprintf("%s-%s", self::QUEUE_NAME, $data);
    }

    public function isDataValid($data)
    {
        return true;
    }
}
