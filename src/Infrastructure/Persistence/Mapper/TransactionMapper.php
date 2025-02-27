<?php

namespace S2low\Infrastructure\Persistence\Mapper;

use S2low\Domain\Model\Transaction\DTO\DocumentMetierPersistenceDTO;
use S2low\Domain\Model\Transaction\DTO\TransactionPersistenceDTO;
use S2low\Domain\Model\ValueObject\ProtocolTransaction;
use S2low\Domain\Model\ValueObject\StatusTransaction;
use S2low\Infrastructure\Persistence\Entity\ActesEnvelopes;
use S2low\Infrastructure\Persistence\Entity\ActesTransactions;

class TransactionMapper
{
    private TransactionStatusListUpdateMapper $transactionStatusListUpdateMapper;

    /**
     * @param TransactionStatusListUpdateMapper $transactionStatusListUpdateMapper
     */
    public function __construct(TransactionStatusListUpdateMapper$transactionStatusListUpdateMapper)
    {
        $this->transactionStatusListUpdateMapper = $transactionStatusListUpdateMapper;
    }


    public function mapToTransaction(
        ActesTransactions $acte,
        ActesEnvelopes $enveloppe,
        string $prefixArchivePath,
        Array $actesHistoriqueStatut
    ): TransactionPersistenceDTO
    {
        $archive =  new DocumentMetierPersistenceDTO(
            $enveloppe->getFilePath(),
            false,
            $acte->isLu(),
            $acte->isAntivirusCheck(),
            $prefixArchivePath
        );

        $transactionStatusListUpdatePersistenceDto = $this->transactionStatusListUpdateMapper->mapToTransactionStatusListUpdatePersistenceDTO($actesHistoriqueStatut);

        return new TransactionPersistenceDTO(
            $acte->getId(),
            $archive,
            ProtocolTransaction::ACTE,
            StatusTransaction::from($acte->getLastStatusId()),
            $transactionStatusListUpdatePersistenceDto
        );
    }
}