<?php

declare(strict_types=1);

namespace S2low\Enum;

enum TransactionSortColumn: string
{
    case Id = 'id';
    case SubmissionDate = 'submission_date';

    public static function fromQuery(mixed $order): self
    {
        return $order === null || $order === self::Id->value ? self::Id : self::SubmissionDate;
    }
}
