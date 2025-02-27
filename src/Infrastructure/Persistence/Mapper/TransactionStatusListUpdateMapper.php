<?php

namespace S2low\Infrastructure\Persistence\Mapper;

use S2low\Domain\Model\Transaction\DTO\TransactionStatusHistoryPersistenceDTO;
use S2low\Domain\Model\Transaction\DTO\TransactionStatusListUpdatePersistenceDTO;

class TransactionStatusListUpdateMapper
{
    public function mapToTransactionStatusListUpdatePersistenceDTO(array $actesHistoriqueStatut) : TransactionStatusListUpdatePersistenceDTO
    {
        $historiqueStatutDTO = [];
        foreach ($actesHistoriqueStatut as $element) {
            $historiqueStatutDTO[] = new TransactionStatusHistoryPersistenceDTO(
                $element->getId(),
                $element->getTransaction()->getId(),
                $element->getStatus()->getId(),
                (new \DateTimeImmutable())->setTimestamp($element->getDate()->getTimestamp()),
                $element->getMessage()
            );
        }

        return new TransactionStatusListUpdatePersistenceDTO(
            ...$historiqueStatutDTO
        );
    }
}