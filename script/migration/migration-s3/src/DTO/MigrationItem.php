<?php

namespace App\DTO;

class MigrationItem
{
    public function __construct(
        readonly public int $id,
        readonly public string $key,
        readonly public string $type,
        readonly public string $date,
        readonly public string $siren
    ) {
    }
}
