<?php

namespace S2low\Infrastructure\Persistence\Mapper;

use S2low\Domain\Model\Transaction\Transaction;
use S2low\Domain\Model\ValueObject\ProtocolTransaction;
use S2low\Domain\Model\ValueObject\StatusTransaction;
use S2low\Entity\ActesEnvelopes;
use S2low\Entity\ActesStatus;
use S2low\Entity\ActesTransactions;

class TransactionMapper
{
    private FileMapper $fileMapper;

    public function __construct(FileMapper $fileMapper)
    {
        $this->fileMapper = $fileMapper;
    }

    public function mapToTransaction(ActesTransactions $acte, ActesEnvelopes $enveloppe, ActesStatus $acteStatus): Transaction
    {
        $transaction = new Transaction();
        $transaction->setId($acte->getId());

        $acteFile = $this->fileMapper->mapFromPath($enveloppe->getFilePath());

        $transaction->setActeFile(
            $acteFile
        );
        $transaction->setStatus(StatusTransaction::from($acteStatus->getId()));
        $transaction->setProtocolTransaction(ProtocolTransaction::ACTE);

        return $transaction;
    }
}