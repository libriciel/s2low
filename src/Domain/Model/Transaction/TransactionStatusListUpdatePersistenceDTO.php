<?php

namespace S2low\Domain\Model\Transaction;

use S2low\Domain\Model\Transaction\DTO\TransactionPersistenceDTO;

class TransactionStatusListUpdatePersistenceDTO
{
    /**
     * @var array|TransactionPersistenceDTO[]
     */
    public array $transactionStatusList;

    /**
     * @param array $transactionStatusList
     */
    public function __construct(TransactionPersistenceDTO ...$transactionStatusList)
    {
        $this->transactionStatusList = $transactionStatusList;
    }

    public function toModel(): TransactionStatusListUpdate
    {
        $transactionStatusList = [];
        foreach ($this->transactionStatusList as $transactionStatus) {
            $transactionStatusList[] = $transactionStatus->toModel();
        }

        return new TransactionStatusListUpdate(
            ...$transactionStatusList
        );
    }
}