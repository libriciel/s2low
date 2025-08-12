<?php

namespace S2lowLegacy\Class\helios;

use S2lowLegacy\Class\IWorker;
use Exception;
use S2lowLegacy\Model\HeliosTransactionsSQL;

class HeliosVerificationSaeWorker implements IWorker
{
    public const QUEUE_NAME = 'helios-verification-sae';

    private $heliosVerificationSAE;
    private $heliosTransactionsSQL;

    public function __construct(
        HeliosVerificationSAE $heliosVerificationSAE,
        HeliosTransactionsSQL $heliosTransactionsSQL
    ) {
        $this->heliosVerificationSAE = $heliosVerificationSAE;
        $this->heliosTransactionsSQL = $heliosTransactionsSQL;
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
        return $this->heliosTransactionsSQL->getTransactionToPrepareToSAE(
            HeliosPrepareSaeWorker::NB_DAYS_ARCHIVE_AFTER,
            0,
            true,
            [HeliosStatusSQL::ENVOYER_AU_SAE]
        );
    }

    /**
     * @param $data
     * @return void
     * @throws Exception
     */
    public function work($data)
    {
        $this->heliosVerificationSAE->verifArchive($data);
    }

    public function isDataValid($data): bool
    {
        return true;
    }
}
