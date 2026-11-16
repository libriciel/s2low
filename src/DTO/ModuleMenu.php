<?php

declare(strict_types=1);

namespace S2low\DTO;

use S2low\Enum\AdministeredModule;
use S2low\Enum\Module;
use S2low\Enum\UserRole;

final readonly class ModuleMenu
{
    public array $transactions;
    public array $statistics;
    public array $exports;
    public array $administrations;

    /**
     * @param list<AdministeredModule> $modulesAdministeredByGroup
     */
    public function __construct(UserRole $role, array $accessibleModules, array $modulesAdministeredByGroup = [])
    {
        $transactions = [];
        $knownModules = [];
        foreach ($accessibleModules as $accessibleModule) {
            $transactions[] = new ModuleMenuEntry(
                (string)$accessibleModule['name'],
                (string)$accessibleModule['menu_entry'],
            );

            $module = Module::fromId((int)$accessibleModule['id']);
            if ($module !== null) {
                $knownModules[] = $module;
            }
        }

        $supervisedModules = $role->isGroupAdmin() ? self::modulesOf($modulesAdministeredByGroup) : $knownModules;

        $statistics = [];
        $exports = [];
        foreach ($supervisedModules as $module) {
            if ($module->hasStatisticsPage()) {
                $statistics[] = ModuleMenuEntry::fromModule($module);
            }
            if ($role->isAnyAdmin() && $module->hasExportPage()) {
                $exports[] = ModuleMenuEntry::fromModule($module);
            }
        }

        $administrations = [];
        foreach ($knownModules as $module) {
            if ($role->isSuperAdmin() && $module->hasAdministrationPage()) {
                $administrations[] = ModuleMenuEntry::fromModule($module);
            }
        }

        $this->transactions = $transactions;
        $this->statistics = $statistics;
        $this->exports = $exports;
        $this->administrations = $administrations;
    }

    /**
     * @param list<AdministeredModule> $administeredModules
     * @return list<Module>
     */
    private static function modulesOf(array $administeredModules): array
    {
        $modules = [];
        foreach ($administeredModules as $administeredModule) {
            $modules[] = Module::from($administeredModule->value);
        }
        return $modules;
    }
}
