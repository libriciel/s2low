<?php

namespace App\Migration;

use App\DTO\MigrationItem;
use Generator;

interface MigrationSourceInterface
{
    public function getIdentifier(): string;

    /**
     * @param int $lastProcessedId
     * @return Generator<MigrationItem>
     */
    public function getItems(int $lastProcessedId): Generator;
}
