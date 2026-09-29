<?php

declare(strict_types=1);

namespace S2low\Tests\DTO;

use PHPUnit\Framework\TestCase;
use S2low\DTO\AdministeringGroups;
use S2low\Enum\AdministeredModule;

class AdministeringGroupsTest extends TestCase
{
    private const int GROUP = 2;

    public function testDesignatingGroupZeroLeavesTheModuleWithoutGroup(): void
    {
        $groups = AdministeringGroups::none()->designate(AdministeredModule::ACTES, 0);

        self::assertFalse($groups->isDesignatedFor(AdministeredModule::ACTES));
        self::assertTrue($groups->isEmpty());
    }

    public function testAnAuthorityWithoutGroupForAModuleLeavesItWithoutGroup(): void
    {
        $groups = AdministeringGroups::fromAuthorityInfo([
            AdministeredModule::ACTES->groupColumn() => null,
            AdministeredModule::HELIOS->groupColumn() => self::GROUP,
        ]);

        self::assertFalse($groups->isDesignatedFor(AdministeredModule::ACTES));
        self::assertSame(self::GROUP, $groups->groupIdFor(AdministeredModule::HELIOS));
    }
}
