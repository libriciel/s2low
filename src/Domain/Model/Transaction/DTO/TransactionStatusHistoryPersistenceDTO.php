<?php

namespace S2low\Domain\Model\Transaction\DTO;

use S2low\Domain\Model\Transaction\TransactionStatusHistory;
use S2low\Domain\Model\ValueObject\DateUpdateStatusTransaction;
use S2low\Domain\Model\ValueObject\StatusTransaction;

class TransactionStatusHistoryPersistenceDTO
{
    public function __construct(
        public int $id,
        public int $transactionId,
        public string $status,
        public \DateTimeImmutable $date,
        public string $message)
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