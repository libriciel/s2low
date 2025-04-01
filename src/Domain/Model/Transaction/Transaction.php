<?php

namespace S2low\Domain\Model\Transaction;

use S2low\Domain\Exception\BadStatusTransactionException;
use S2low\Domain\Exception\DocumentMetierNotFoundException;
use S2low\Domain\Model\Transaction\DTO\TransactionPersistenceDTO;
use S2low\Domain\Model\ValueObject\DateUpdateStatusTransaction;
use S2low\Domain\Model\ValueObject\ProtocolTransaction;
use S2low\Domain\Model\ValueObject\StatusTransaction;

class Transaction
{
    private ?int $id;
    private DocumentMetier $archive;
    private ProtocolTransaction $protocolTransaction;
    private StatusTransaction $status;
    private TransactionStatusListUpdate $transactionStatusListUpdate;

    public function __construct(
        int $id,
        DocumentMetier $documentMetier,
        ProtocolTransaction $protocolTransaction,
        StatusTransaction $status,
        TransactionStatusListUpdate $transactionStatusListUpdate
    )
    {
        $this->id = $id;
        $this->archive = $documentMetier;
        $this->protocolTransaction = $protocolTransaction;
        $this->status = $status;
        $this->transactionStatusListUpdate = $transactionStatusListUpdate;
    }

    /**
     * @param $goodStatus
     * @return void
     */
    public function assertStatusIs($goodStatus) : void
    {
        if ( $this->status !== $goodStatus ) {
            throw new BadStatusTransactionException($this->id, $goodStatus, $this->status);
        }
    }

    public function reportVirusPresence(): void
    {
        $this->archive->markInfected();
        $this->archive->antivirusCheck();
        $this->updateStatusTo(
            StatusTransaction::ERREUR,
            "L'archive est infectée par un virus. Retour de l'antivirus."
        );
    }

    public function confirmVirusAbsence(): void
    {
        $this->archive->antivirusCheck();
    }

    public function toPersistenceDto(): TransactionPersistenceDTO
    {
        return new TransactionPersistenceDTO(
            $this->id,
            $this->archive->toPersistenceDto(),
            $this->protocolTransaction,
            $this->status,
            $this->transactionStatusListUpdate->toPersistenceDto()
        );
    }

    private function updateStatusTo(StatusTransaction $newStatus, string $statusUpdateMessage): void
    {
        $this->status = $newStatus;
        $this->transactionStatusListUpdate->add(
            new TransactionStatusHistory(
                null,
                $this->id,
                $this->status,
                new DateUpdateStatusTransaction(),
                $statusUpdateMessage,
            )
        );
    }

    public function getArchive(): DocumentMetier
    {
        return $this->archive;
    }

    /**
     * @return void
     * @throws BadStatusTransactionException | DocumentMetierNotFoundException
     */
    public function readyToBeScannedOrThrow(): void
    {
        $this->assertStatusIs(StatusTransaction::CREE);
        $this->archive->assertFileIsValid();
    }
}
