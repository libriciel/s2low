<?php

declare(strict_types=1);

namespace S2low\DTO;

final readonly class AuthoritySearch
{
    public function __construct(
        public int $authorityTypeId = 0,
        public string $name = '',
        public string $siren = '',
        public string $siret = '',
        public int $administeringGroupId = 0,
        public int $actesGroupId = 0,
        public int $heliosGroupId = 0,
    ) {
    }
}
