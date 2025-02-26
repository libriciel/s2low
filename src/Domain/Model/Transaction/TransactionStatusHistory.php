<?php

namespace S2low\Domain\Model\Transaction;

use S2low\Domain\Model\ValueObject\DateUpdateStatusTransaction;
use S2low\Domain\Model\ValueObject\StatusTransaction;

class TransactionStatusHistory
{
    private ?int $id;
    private int $transactionId;
    private StatusTransaction $status;
    private DateUpdateStatusTransaction $date;
    private int $message;

    public function __construct(
         int|null $id,
         int $transactionId,
         StatusTransaction $status,
         DateUpdateStatusTransaction $date,
         int $message,
    )
    {
        $this->id = $id;
        $this->transactionId = $transactionId;
        $this->status = $status;
        $this->date = $date;
        $this->message = $message;
    }

    public function toPersistenceDto(): TransactionStatusHistoryPersistenceDTO
    {
        return new TransactionStatusHistoryPersistenceDTO(
            $this->id,
            $this->transactionId,
            $this->status->value,
            $this->date->getDate(),
            $this->message,
        );
    }
}