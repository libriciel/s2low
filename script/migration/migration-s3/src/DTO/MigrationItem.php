<?php

namespace App\DTO;

class MigrationItem
{
    public function __construct(
        readonly public int $id,
        readonly public string $oldKey,
        readonly public string $newKey,
        readonly public string $type,
        readonly public string $date,
        public ?string $bucket = null
    ) {
    }
}
