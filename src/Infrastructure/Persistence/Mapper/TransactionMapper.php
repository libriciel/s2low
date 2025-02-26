<?php

namespace S2low\Infrastructure\Persistence\Mapper;

use S2low\Domain\Model\Transaction\DTO\DocumentMetierPersistenceDTO;
use S2low\Domain\Model\Transaction\DTO\TransactionPersistenceDTO;
use S2low\Domain\Model\ValueObject\ProtocolTransaction;
use S2low\Domain\Model\ValueObject\StatusTransaction;
use S2low\Infrastructure\Persistence\Entity\ActesEnvelopes;
use S2low\Infrastructure\Persistence\Entity\ActesStatus;
use S2low\Infrastructure\Persistence\Entity\ActesTransactions;
use S2low\Infrastructure\Persistence\Entity\ActesTransactionsWorkflow;

class TransactionMapper
{
    public function mapToTransaction(ActesTransactions $acte, ActesEnvelopes $enveloppe, ActesStatus $acteStatus, ActesTransactionsWorkflow $actesTransactionsWorkflow): TransactionPersistenceDTO
    {
        $documentMetierDto =  new DocumentMetierPersistenceDTO(
            $enveloppe->getFilePath(),
            $acte->isAntivirusCheck(),
            $acte->isLu()
        );

        return new TransactionPersistenceDTO(
            $acte->getId(),
            $documentMetierDto,
            ProtocolTransaction::ACTE,
            StatusTransaction::from($acte->getLastStatusId()),
            //TODO recuperer la liste des AcctesTransaction Workflow
        );
    }
}