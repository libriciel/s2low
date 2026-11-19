<?php

declare(strict_types=1);

namespace Test\PHPUnit\Twig\Extensions;

use S2low\Security\SecurityUser;
use S2low\Twig\Extensions\ModuleExtension;
use S2low\Enum\UserRole;
use S2lowLegacy\Lib\SQLQuery;
use S2lowLegacy\Model\GroupSQL;
use S2lowLegacy\Model\ModuleSQL;
use S2lowLegacy\Model\UserSQL;
use S2lowTestCase;

class ModuleExtensionTest extends S2lowTestCase
{
    private const int SUPER_ADMIN = 1;
    private const int USER_WITHOUT_PERMISSION = 5;
    private const int USER_GRANTED_ON_HELIOS_ONLY = 8;
    private const int GROUP_ADMIN = 13;
    private const int OTHER_GROUP = 2;

    public function testUserWithoutPermissionGetsAnEmptyMenu(): void
    {
        $menu = $this->extension()->getModuleMenu($this->user(self::USER_WITHOUT_PERMISSION));

        static::assertSame([], $menu->transactions);
        static::assertSame([], $menu->statistics);
    }

    public function testUserGetsOnlyTheModulesHeIsGrantedOn(): void
    {
        $menu = $this->extension()->getModuleMenu($this->user(self::USER_GRANTED_ON_HELIOS_ONLY));

        static::assertSame(['helios'], array_column($menu->transactions, 'name'));
        static::assertSame(['Transactions Helios'], array_column($menu->transactions, 'label'));
        static::assertSame(['helios'], array_column($menu->statistics, 'name'));
    }

    public function testSuperAdminGetsEveryActiveModule(): void
    {
        $menu = $this->extension()->getModuleMenu($this->user(self::SUPER_ADMIN));

        static::assertSame(['actes', 'helios', 'mail'], array_column($menu->transactions, 'name'));
        static::assertSame(['actes', 'helios'], array_column($menu->administrations, 'name'));
    }

    public function testGroupAdminMenuFollowsTheModulesItsGroupAdministers(): void
    {
        $sqlQuery = self::getContainer()->get(SQLQuery::class);
        $sqlQuery->query('UPDATE users SET role = ? WHERE id = ?', [UserRole::AdministrateurGroupe->value, self::GROUP_ADMIN]);
        $sqlQuery->query('UPDATE authorities SET actes_group_id = ?', [self::OTHER_GROUP]);

        $menu = $this->extension()->getModuleMenu($this->user(self::GROUP_ADMIN));

        static::assertSame(['helios'], array_column($menu->statistics, 'name'));
        static::assertSame([], $menu->exports);
    }

    private function extension(): ModuleExtension
    {
        return new ModuleExtension(
            self::getContainer()->get(ModuleSQL::class),
            self::getContainer()->get(GroupSQL::class),
        );
    }

    private function user(int $userId): SecurityUser
    {
        return new SecurityUser(self::getContainer()->get(UserSQL::class)->getUserById($userId));
    }
}
