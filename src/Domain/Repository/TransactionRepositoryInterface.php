<?php

namespace S2low\Domain\Repository;

use Doctrine\ORM\EntityNotFoundException;
use S2low\Domain\Model\Transaction\Transaction;

interface TransactionRepositoryInterface
{
    /**
     * @param string $acteId
     * @return Transaction
     * @throws EntityNotFoundException
     */
    public function findTransactionFromActeId(string $acteId): Transaction;

    public function updateTransactionAnalyseAntivirusPositive(Transaction $transaction): void;
    public function updateTransactionAnalyseAntivirusNegative(Transaction $transaction) : void;

}