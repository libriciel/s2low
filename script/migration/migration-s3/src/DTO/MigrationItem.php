<?php

namespace App\DTO;

use App\Enum\Status;
use App\Enum\Type;

class MigrationItem
{
    public function __construct(
        readonly public int $id,
        readonly public ?string $oldKey,
        readonly public string $newKey,
        readonly public Type $type,
        readonly public string $date,
        readonly public Status $status,
        public ?string $bucket = null
    ) {
    }
}
