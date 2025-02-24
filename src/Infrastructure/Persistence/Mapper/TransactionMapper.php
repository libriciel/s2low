<?php

namespace S2low\Infrastructure\Persistence\Mapper;

use S2low\Domain\Model\Transaction\Transaction;
use S2low\Domain\Model\ValueObject\ProtocolTransaction;
use S2low\Domain\Model\ValueObject\StatusTransaction;
use S2low\Entity\ActesEnvelopes;
use S2low\Entity\ActesTransactions;
use Symfony\Component\HttpFoundation\File\File;

class TransactionMapper
{
    public static function mapToTransaction(ActesTransactions $acte, ActesEnvelopes $enveloppe, int $acteStatus): Transaction
    {
        $transaction = new Transaction();
//        $transaction->setId($acte->getId());
//        $transaction->setActeFile(
//            new File($enveloppe->getFilePath())
//        );
//        $transaction->setStatus(StatusTransaction::from($acteStatus));
//        $transaction->setProtocolTransaction(ProtocolTransaction::ACTE);

        return $transaction;
    }
}