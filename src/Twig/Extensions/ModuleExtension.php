<?php

namespace S2low\Twig\Extensions;

use S2low\Enum\Module;
use S2lowLegacy\Model\ModuleSQL;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ModuleExtension extends AbstractExtension
{
    public function __construct(
        private readonly ModuleSQL $moduleSQL,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getModules', [$this, 'getModules']),
            new TwigFunction('getModulesWithAdminPage', [$this, 'getModulesWithAdminPage']),
            new TwigFunction('getModulesWithStatus', [$this, 'getModulesWithStatus']),
            new TwigFunction('getUserPermOnModule', [$this, 'getUserPermOnModule']),
        ];
    }

    public function getModules(): array
    {
        return [
            ['name' => 'actes', 'menu_entry' => 'Transactions Actes'],
            ['name' => 'helios', 'menu_entry' => 'Transactions Helios'],
            ['name' => 'mail', 'menu_entry' => 'Transactions Mail'],
        ];
    }

    public function getModulesWithAdminPage(): array
    {
        return [
            ['name' => 'actes', 'menu_entry' => 'Transactions Actes'],
            ['name' => 'helios', 'menu_entry' => 'Transactions Helios'],
        ];
    }

    public function getModulesWithStatus(): array
    {
        return [
            ['name' => 'actes'],
            ['name' => 'helios'],
        ];
    }

    public function getUserPermOnModule(string $moduleName, int $userId): string
    {
        return $this->moduleSQL->getInfoPerms(Module::fromName($moduleName)->id(), $userId);
    }
}
