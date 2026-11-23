<?php

declare(strict_types=1);

namespace S2low\Services\TransactionList;

use DateTimeImmutable;
use Exception;
use UnexpectedValueException;

final class ListLineReader
{
    public static function submissionDate(array $line, int $transactionId): ?DateTimeImmutable
    {
        $submissionDate = $line['submission_date'] ?? null;
        if ($submissionDate === null || $submissionDate === '') {
            return null;
        }

        try {
            return new DateTimeImmutable((string) $submissionDate);
        } catch (Exception $exception) {
            throw new UnexpectedValueException(
                "Transaction $transactionId : date de dépôt invalide « $submissionDate »",
                0,
                $exception
            );
        }
    }

    public static function fullName(array $line): string
    {
        return trim(($line['givenname'] ?? '') . ' ' . ($line['name'] ?? ''));
    }
}
