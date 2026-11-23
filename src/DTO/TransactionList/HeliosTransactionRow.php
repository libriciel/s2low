<?php

declare(strict_types=1);

namespace S2low\DTO\TransactionList;

use DateTimeImmutable;

final readonly class HeliosTransactionRow
{
    public function __construct(
        public int $id,
        public string $showUrl,
        public string $filename,
        public string $nomFic,
        public string $authorityName,
        public ?DateTimeImmutable $submittedAt,
        public StatusBadge $status,
        public string $followerName,
        public bool $isSelectable,
    ) {
    }
}
