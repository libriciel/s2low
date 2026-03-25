<?php

namespace App\Migration;

use App\DTO\MigrationItem;
use Generator;

interface MigrationSourceInterface
{
    public function getIdentifier(): string;

    /**
     * @param int $lastProcessedId
     * @param string|null $minDate
     * @param string|null $maxDate
     * @return Generator<MigrationItem>
     */
    public function getItems(int $lastProcessedId, ?string $minDate = null, ?string $maxDate = null): Generator;

    public function getHandledTransactions(): Generator;
}
