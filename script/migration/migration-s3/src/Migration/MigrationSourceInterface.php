<?php

namespace App\Migration;

use App\DTO\MigrationItem;
use App\Enum\Type;
use Generator;

interface MigrationSourceInterface
{
    public function getType(): Type;

    /**
     * @param int $lastProcessedId
     * @param string|null $minDate
     * @param string|null $maxDate
     * @return Generator<MigrationItem>
     */
    public function getItems(int $lastProcessedId, ?string $minDate = null, ?string $maxDate = null): Generator;
}
