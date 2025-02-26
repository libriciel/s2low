<?php

namespace S2low\Domain\Repository;

use Doctrine\ORM\EntityNotFoundException;
use S2low\Domain\Model\Transaction\DTO\TransactionPersistenceDTO;

interface TransactionRepositoryInterface
{
    /**
     * @param string $acteId
     * @return TransactionPersistenceDTO
     * @throws EntityNotFoundException
     */
    public function findById(string $acteId): TransactionPersistenceDTO;
    public function updateTransactionAnalyseAntivirusPositive(TransactionPersistenceDTO $transactionDTO): void;
    public function updateTransactionAnalyseAntivirusNegative(TransactionPersistenceDTO $transaction) : void;
    public function save(TransactionPersistenceDTO $transactionPersistenceDTO);

}