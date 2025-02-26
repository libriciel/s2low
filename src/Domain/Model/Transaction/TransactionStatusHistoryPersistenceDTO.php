<?php

namespace S2low\Domain\Model\Transaction;

use S2low\Domain\Model\ValueObject\DateUpdateStatusTransaction;
use S2low\Domain\Model\ValueObject\StatusTransaction;

class TransactionStatusHistoryPersistenceDTO
{
    public function __construct(
        public int $id,
        public int $transactionId,
        public string $status,
        public string $date,
        public int $message)
    {
    }

    public function toModel(): TransactionStatusHistory
    {
        return new TransactionStatusHistory(
            $this->id,
            $this->transactionId,
            StatusTransaction::from($this->status),
            new DateUpdateStatusTransaction($this->date),
            $this->message
        );
    }
}