<?php

declare(strict_types=1);

namespace Test\PHPUnit\Twig\Extensions;

use S2low\Enum\Module;
use S2low\Security\SecurityUser;
use S2low\Twig\Extensions\ModuleExtension;
use S2lowLegacy\Model\ModuleSQL;
use S2lowLegacy\Model\UserSQL;
use S2lowTestCase;

class ModuleExtensionTest extends S2lowTestCase
{
    private const int SUPER_ADMIN = 1;
    private const int USER_WITHOUT_PERMISSION = 5;
    private const int USER_GRANTED_ON_HELIOS_ONLY = 8;

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
        static::assertSame(['Transactions Helios'], array_column($menu->transactions, 'menu_entry'));
        static::assertSame([Module::HELIOS], $menu->statistics);
    }

    public function testSuperAdminGetsEveryActiveModule(): void
    {
        $menu = $this->extension()->getModuleMenu($this->user(self::SUPER_ADMIN));

        static::assertSame(['actes', 'helios', 'mail'], array_column($menu->transactions, 'name'));
        static::assertSame([Module::ACTES, Module::HELIOS], $menu->administrations);
    }

    private function extension(): ModuleExtension
    {
        return new ModuleExtension(self::getContainer()->get(ModuleSQL::class));
    }

    private function user(int $userId): SecurityUser
    {
        return new SecurityUser(self::getContainer()->get(UserSQL::class)->getUserById($userId));
    }
}
