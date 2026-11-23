<?php

declare(strict_types=1);

namespace S2low\DTO\TransactionList;

use DateTimeImmutable;
use S2lowLegacy\Class\actes\TypeTransaction;

final readonly class ActesTransactionRow
{
    /**
     * @param list<ActesFollowUp> $followUps
     */
    public function __construct(
        public int $id,
        public string $showUrl,
        public string $internalNumber,
        public TypeTransaction $type,
        public string $typeLabel,
        public string $subject,
        public string $nature,
        public string $authorityName,
        public ?DateTimeImmutable $submittedAt,
        public StatusBadge $status,
        public string $followerName,
        public bool $isSelectable,
        public ?string $archiveUrl,
        public array $followUps,
    ) {
    }

    public function isActTransmission(): bool
    {
        return $this->type === TypeTransaction::TransmissionActe;
    }
}
