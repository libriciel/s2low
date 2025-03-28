<?php

namespace S2low\Domain\Model\Transaction;

use S2low\Domain\Model\Transaction\DTO\TransactionStatusListUpdatePersistenceDTO;

class TransactionStatusListUpdate
{
    private array $transactionStatusList;

    public function __construct(TransactionStatusHistory ...$transactionStatusList)
    {
        $this->transactionStatusList = $transactionStatusList;
    }

    public function all(): array
    {
        return $this->transactionStatusList;
    }

    public function add(TransactionStatusHistory $transactionStatusHistory): void
    {
        $this->transactionStatusList[] = $transactionStatusHistory;
    }

    public function toPersistenceDto(): TransactionStatusListUpdatePersistenceDTO
    {
        $transactionStatusList = [];
        foreach ($this->transactionStatusList as $transactionStatusHistory) {
            $transactionStatusList[] = $transactionStatusHistory->toPersistenceDto();
        }

        return new TransactionStatusListUpdatePersistenceDTO(...$transactionStatusList);
    }
}
