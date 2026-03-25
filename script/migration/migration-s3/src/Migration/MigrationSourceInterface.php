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
     * @return Generator<MigrationItem>
     */
    public function getItems(int $lastProcessedId, ?string $minDate = null): Generator;

    public function getHandledTransactions(): Generator;
}
