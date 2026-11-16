<?php

declare(strict_types=1);

namespace S2low\DTO;

final readonly class GroupAuthorityCount
{
    public function __construct(
        public int $groupId,
        public string $groupName,
        public int $authorityCount,
    ) {
    }
}
