<?php

declare(strict_types=1);

namespace S2low\DTO\TransactionList;

final readonly class HeliosTransactionTable
{
    /**
     * @param list<HeliosTransactionRow> $rows
     */
    public function __construct(
        public array $rows,
        public TransactionTableSorting $sorting,
        public bool $showsAuthority,
    ) {
    }

    public function hasSelectableRow(): bool
    {
        foreach ($this->rows as $row) {
            if ($row->isSelectable) {
                return true;
            }
        }

        return false;
    }
}
