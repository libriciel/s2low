<?php

namespace S2low\Domain\Model\Transaction;

use S2low\Domain\Exception\BadStatusTransactionException;
use S2low\Domain\Model\Transaction\DTO\TransactionPersistenceDTO;
use S2low\Domain\Model\ValueObject\DateUpdateStatusTransaction;
use S2low\Domain\Model\ValueObject\ProtocolTransaction;
use S2low\Domain\Model\ValueObject\StatusTransaction;
use S2low\Domain\Port\AntivirusFilesScannerInterface;
//    private UniqueId $uniqueId;
//    private TransactionType $type;
//    private StatutTransaction $statut;
//    private Nature $nature;
//    private EnveloppeArchive $enveloppeArchive;
//    private Classification $classification;
//    private Collectivite $collectivite;
//    private Date $dateDeLaDecision;
//    private string $commentaire;

class Transaction
{
    private ?int $id;
    private DocumentMetier $documentMetier;
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
        $this->documentMetier = $documentMetier;
        $this->protocolTransaction = $protocolTransaction;
        $this->status = $status;
        $this->transactionStatusListUpdate = $transactionStatusListUpdate;
    }

    /**
     * @return void
     * @throws BadStatusTransactionException
     */
    public function readyToScanOrThrow() : void
    {
        if ( $this->status !== StatusTransaction::CREE ) {
            throw new BadStatusTransactionException($this->id, StatusTransaction::CREE, $this->status);
        }
    }

    public function scanWith(AntivirusFilesScannerInterface $scanner): void
    {
        $this->readyToScanOrThrow();
        $this->documentMetier->scanWith($scanner);
    }

    public function analyseAntivirusPositive(): void
    {
        $this->documentMetier->markInfected();
        $this->updateStatusTo(
            StatusTransaction::ERREUR,
            "L'archive est infectée par un virus. Retour de l'antivirus."
        );
    }

    public function analyseAntivirusNegative()
    {
        // TODO
    }

    public function toPersistenceDto(): TransactionPersistenceDTO
    {
        return new TransactionPersistenceDTO(
            $this->id,
            $this->documentMetier->toPersistenceDto(),
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
}