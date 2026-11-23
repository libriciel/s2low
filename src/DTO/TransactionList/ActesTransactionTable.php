<?php

declare(strict_types=1);

namespace S2low\DTO\TransactionList;

final readonly class ActesTransactionTable
{
    /**
     * @param list<ActesTransactionRow> $rows
     */
    public function __construct(
        public array $rows,
        public TransactionTableSorting $sorting,
        public bool $showsAuthority,
    ) {
    }
}
