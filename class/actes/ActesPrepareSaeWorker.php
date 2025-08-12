<?php

namespace S2lowLegacy\Class\actes;

use S2lowLegacy\Class\IWorker;
use S2lowLegacy\Class\S2lowLogger;

class ActesPrepareSaeWorker implements IWorker
{
    public const QUEUE_NAME = 'actes-prepare-sae';

    public const NB_DAYS_ARCHIVE_AFTER = 62;

    private $actesTransactionsSQL;
    private $s2lowLogger;
    private $actesPrepareEnvoiSAE;

    public function __construct(
        ActesTransactionsSQL $actesTransactionsSQL,
        S2lowLogger $s2lowLogger,
        ActesPrepareEnvoiSAE $actesPrepareEnvoiSAE
    ) {
        $this->actesTransactionsSQL = $actesTransactionsSQL;
        $this->s2lowLogger = $s2lowLogger;
        $this->actesPrepareEnvoiSAE = $actesPrepareEnvoiSAE;
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
        return $this->actesTransactionsSQL->getTransactionToArchive(
            self::NB_DAYS_ARCHIVE_AFTER
        );
    }

    /**
     * @param $transaction_id
     * @return void
     */
    public function work($transaction_id)
    {
        $transaction_info = $this->actesTransactionsSQL->getInfo($transaction_id);
        $this->actesPrepareEnvoiSAE->setArchiveEnAttenteEnvoiSEA(
            $transaction_info['user_id'],
            $transaction_id
        );
    }

    public function isDataValid($data): bool
    {
        return true;
    }
}
