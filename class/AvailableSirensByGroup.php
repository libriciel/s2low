<?php

declare(strict_types=1);

namespace S2lowLegacy\Class;

use S2lowLegacy\Model\AuthorityGroupSirenSQL;
use S2lowLegacy\Model\GroupSQL;

class AvailableSirensByGroup
{
    public function __construct(
        private GroupSQL $groupSQL,
        private AuthorityGroupSirenSQL $authorityGroupSirenSQL
    ) {
    }
    public function get(int $authorityId): array
    {
        $groups = $this->groupSQL->getGroupsIdName();
        $sirensByGroup = [];

        foreach ($groups as $key => $value) {
            $sirensByGroup[$key] = $this->authorityGroupSirenSQL->getAvailableSiren($key, $authorityId);
        }
        return [$groups, $sirensByGroup];
    }
}
