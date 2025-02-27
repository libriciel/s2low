<?php

namespace S2low\Domain\Model\Transaction\DTO;

use S2low\Domain\Model\Transaction\Transaction;
use S2low\Domain\Model\Transaction\DTO\TransactionStatusListUpdatePersistenceDTO;
use S2low\Domain\Model\ValueObject\ProtocolTransaction;
use S2low\Domain\Model\ValueObject\StatusTransaction;

class TransactionPersistenceDTO
{
    public function __construct(
        public int                          $id,
        public DocumentMetierPersistenceDTO $documentMetierDTO,
        public ProtocolTransaction          $protocolTransaction,
        public StatusTransaction            $status,
        public TransactionStatusListUpdatePersistenceDTO                        $historyPersistenceDTO
        )
    {}

    function toModel(): Transaction
    {
        return new Transaction(
            $this->id,
            $this->documentMetierDTO->toModel(),
            $this->protocolTransaction,
            $this->status,
            $this->historyPersistenceDTO->toModel()
        );
    }
}