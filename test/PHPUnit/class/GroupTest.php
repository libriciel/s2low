<?php

declare(strict_types=1);

namespace PHPUnit\class;

use S2lowLegacy\Class\Group;
use S2lowTestCase;

class GroupTest extends S2lowTestCase
{
    private const int GROUP_ID = 2;
    private const int AUTHORITY_ID = 2;

    public function testIsNotEmptyWhenAnAuthorityDesignatesItForActesOnly(): void
    {
        $this->designate('actes_group_id', self::GROUP_ID);

        static::assertFalse(Group::isEmpty(self::GROUP_ID));
    }

    public function testIsNotEmptyWhenAnAuthorityDesignatesItForHeliosOnly(): void
    {
        $this->designate('helios_group_id', self::GROUP_ID);

        static::assertFalse(Group::isEmpty(self::GROUP_ID));
    }

    public function testBecomesEmptyOnceNoAuthorityDesignatesItAnymore(): void
    {
        $this->designate('actes_group_id', self::GROUP_ID);
        $this->designate('helios_group_id', self::GROUP_ID);

        $this->designate('actes_group_id', null);
        $this->designate('helios_group_id', null);

        static::assertTrue(Group::isEmpty(self::GROUP_ID));
    }

    private function designate(string $groupColumn, ?int $groupId): void
    {
        $this->getSQLQuery()->query(
            "UPDATE authorities SET $groupColumn = ? WHERE id = ?",
            [$groupId, self::AUTHORITY_ID]
        );
    }
}
