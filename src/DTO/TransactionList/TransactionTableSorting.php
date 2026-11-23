<?php

declare(strict_types=1);

namespace S2low\DTO\TransactionList;

use S2low\Enum\SortDirection;
use S2low\Enum\TransactionSortColumn;

final readonly class TransactionTableSorting
{
    public function __construct(
        public ColumnSort $byId,
        public ColumnSort $bySubmissionDate,
    ) {
    }

    public static function fromQuery(string $pageUrl, array $query): self
    {
        $activeColumn = TransactionSortColumn::fromQuery($query['order'] ?? null);
        $activeDirection = SortDirection::fromQuery($query['sortway'] ?? null);

        $columnSort = static function (TransactionSortColumn $column) use ($pageUrl, $query, $activeColumn, $activeDirection): ColumnSort {
            $isActive = $column === $activeColumn;
            $nextDirection = $isActive ? $activeDirection->reversed() : SortDirection::Desc;
            $parameters = array_merge($query, ['order' => $column->value, 'sortway' => $nextDirection->value]);

            return new ColumnSort($pageUrl . '?' . http_build_query($parameters), $isActive ? $activeDirection : null);
        };

        return new self($columnSort(TransactionSortColumn::Id), $columnSort(TransactionSortColumn::SubmissionDate));
    }
}
