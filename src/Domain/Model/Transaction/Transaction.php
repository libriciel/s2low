<?php

namespace S2low\Domain\Model\Transaction;

use S2low\Domain\Model\ValueObject\ProtocolTransaction;
use S2low\Domain\Model\ValueObject\StatusTransaction;
use Symfony\Component\HttpFoundation\File\File;

class Transaction
{
    public const STATUS_EN_ERREUR = -1;
    private int $id;
    private File $acteFile;
    private ProtocolTransaction $protocolTransaction;
    private StatusTransaction $status;
//    private UniqueId $uniqueId;
//    private TransactionType $type;
//    private StatutTransaction $statut;
//    private Nature $nature;
//    private EnveloppeArchive $enveloppeArchive;
//    private Classification $classification;
//    private Collectivite $collectivite;
//    private Date $dateDeLaDecision;
//    private string $commentaire;


    public function __construct()
    {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getActeFile() : File
    {
        return $this->acteFile;
    }

    public function setActeFile(File $acteFile): void
    {
        $this->acteFile = $acteFile;
    }

    public function getProtocolTransaction() : ProtocolTransaction
    {
        return $this->protocolTransaction;
    }

    public function setProtocolTransaction(ProtocolTransaction $protocolTransaction): void
    {
        $this->protocolTransaction = $protocolTransaction;
    }

    public function getStatus() : StatusTransaction
    {
        return $this->status;
    }
    public function setStatus(StatusTransaction $status): void
    {
        $this->status = $status;
    }
}