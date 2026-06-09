<?php

declare(strict_types=1);

namespace S2lowLegacy\Class;

use S2lowLegacy\Model\AuthorityGroupSirenSQL;
use S2lowLegacy\Model\GroupSQL;

class AvailableSirensByGroup
{
    public function __construct(
        private readonly GroupSQL $groupSQL,
        private readonly AuthorityGroupSirenSQL $authorityGroupSirenSQL,
    ) {
    }
    public function get(?int $authorityId): array
    {
        $groups = $this->groupSQL->getGroupsIdName();
        $sirensByGroup = array_fill_keys(array_keys($groups), []);

        foreach ($this->authorityGroupSirenSQL->getAvailableSirenForAllGroups($authorityId) as $row) {
            $groupId = (int)$row['authority_group_id'];
            if (isset($sirensByGroup[$groupId])) {
                $sirensByGroup[$groupId][] = $row['siren'];
            }
        }
        return [$groups, $sirensByGroup];
    }
}
