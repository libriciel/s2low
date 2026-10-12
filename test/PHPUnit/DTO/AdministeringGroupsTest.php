<?php

declare(strict_types=1);

namespace Test\PHPUnit\DTO;

use PHPUnit\Framework\TestCase;
use S2low\DTO\AdministeringGroups;

class AdministeringGroupsTest extends TestCase
{
    private const GROUP = 42;
    private const OTHER_GROUP = 7;

    public function testAGroupDesignatedForBothModulesAdministers(): void
    {
        $groups = AdministeringGroups::fromAuthorityInfo([
            'actes_group_id' => self::GROUP,
            'helios_group_id' => self::GROUP,
        ]);

        $this->assertTrue($groups->isAdministeredBy(self::GROUP));
    }

    public function testAGroupDesignatedForActesOnlyAdministers(): void
    {
        $groups = AdministeringGroups::fromAuthorityInfo([
            'actes_group_id' => self::GROUP,
            'helios_group_id' => self::OTHER_GROUP,
        ]);

        $this->assertTrue($groups->isAdministeredBy(self::GROUP));
    }

    public function testAGroupDesignatedForHeliosOnlyAdministers(): void
    {
        $groups = AdministeringGroups::fromAuthorityInfo([
            'actes_group_id' => self::OTHER_GROUP,
            'helios_group_id' => self::GROUP,
        ]);

        $this->assertTrue($groups->isAdministeredBy(self::GROUP));
    }

    public function testAGroupDesignatedForNoModuleDoesNotAdminister(): void
    {
        $groups = AdministeringGroups::fromAuthorityInfo([
            'actes_group_id' => self::OTHER_GROUP,
            'helios_group_id' => self::OTHER_GROUP,
        ]);

        $this->assertFalse($groups->isAdministeredBy(self::GROUP));
    }

    public function testAnAuthorityWithoutDesignationIsAdministeredByNobody(): void
    {
        $groups = AdministeringGroups::fromAuthorityInfo([]);

        $this->assertFalse($groups->isAdministeredBy(self::GROUP));
        $this->assertFalse($groups->isAdministeredBy(0));
    }
}
