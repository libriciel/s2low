<?php

declare(strict_types=1);

namespace S2low\Tests\DTO;

use PHPUnit\Framework\TestCase;
use S2low\DTO\ModuleMenu;
use S2low\Enum\Module;
use S2low\Enum\UserRole;

class ModuleMenuTest extends TestCase
{
    private const array ACTES = ['id' => 1, 'name' => 'actes', 'menu_entry' => 'Transactions Actes'];
    private const array HELIOS = ['id' => 2, 'name' => 'helios', 'menu_entry' => 'Transactions Helios'];
    private const array MAIL = ['id' => 3, 'name' => 'mail', 'menu_entry' => 'Transactions Mail'];
    private const array UNKNOWN = ['id' => 99, 'name' => 'inconnu', 'menu_entry' => 'Transactions inconnues'];

    public function testListsAccessibleModulesAsTransactions(): void
    {
        $menu = new ModuleMenu(UserRole::Utilisateur, [self::HELIOS, self::MAIL]);

        self::assertSame([self::HELIOS, self::MAIL], $menu->transactions);
    }

    public function testUserWithoutModuleGetsAnEmptyMenu(): void
    {
        $menu = new ModuleMenu(UserRole::Utilisateur, []);

        self::assertSame([], $menu->transactions);
        self::assertSame([], $menu->statistics);
        self::assertSame([], $menu->exports);
        self::assertSame([], $menu->administrations);
    }

    public function testOffersStatisticsOnlyForAccessibleModulesThatHaveSome(): void
    {
        $menu = new ModuleMenu(UserRole::Utilisateur, [self::HELIOS, self::MAIL]);

        self::assertSame([Module::HELIOS], $menu->statistics);
    }

    public function testUserGetsNeitherExportNorAdministration(): void
    {
        $menu = new ModuleMenu(UserRole::Utilisateur, [self::ACTES, self::HELIOS]);

        self::assertSame([], $menu->exports);
        self::assertSame([], $menu->administrations);
    }

    public function testAuthorityAdminExportsActesWhenItIsAccessible(): void
    {
        $menu = new ModuleMenu(UserRole::AdministrateurCollectivite, [self::ACTES, self::HELIOS]);

        self::assertSame([Module::ACTES], $menu->exports);
    }

    public function testAuthorityAdminWithoutActesHasNoExport(): void
    {
        $menu = new ModuleMenu(UserRole::AdministrateurCollectivite, [self::HELIOS]);

        self::assertSame([], $menu->exports);
    }

    public function testGroupAdminGetsNoAdministrationUtilities(): void
    {
        $menu = new ModuleMenu(UserRole::AdministrateurGroupe, [self::ACTES, self::HELIOS]);

        self::assertSame([], $menu->administrations);
    }

    public function testSuperAdminGetsAdministrationUtilitiesOfModulesThatHaveSome(): void
    {
        $menu = new ModuleMenu(UserRole::SuperAdministrateur, [self::ACTES, self::HELIOS, self::MAIL]);

        self::assertSame([Module::ACTES, Module::HELIOS], $menu->administrations);
    }

    public function testUnknownModuleIsOnlyListedAsTransactions(): void
    {
        $menu = new ModuleMenu(UserRole::SuperAdministrateur, [self::UNKNOWN]);

        self::assertSame([self::UNKNOWN], $menu->transactions);
        self::assertSame([], $menu->statistics);
        self::assertSame([], $menu->exports);
        self::assertSame([], $menu->administrations);
    }
}
