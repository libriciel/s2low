<?php

declare(strict_types=1);

namespace S2low\DTO\TransactionList;

final readonly class ActesFollowUp
{
    public function __construct(
        public string $label,
        public string $url,
    ) {
    }
}
