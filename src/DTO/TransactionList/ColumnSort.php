<?php

declare(strict_types=1);

namespace S2low\DTO\TransactionList;

use S2low\Enum\SortDirection;

final readonly class ColumnSort
{
    public function __construct(
        public string $url,
        public ?SortDirection $activeDirection,
    ) {
    }

    public function isActive(): bool
    {
        return $this->activeDirection !== null;
    }
}
