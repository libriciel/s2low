<?php

declare(strict_types=1);

namespace S2low\DTO;

use S2low\Enum\Module;
use S2low\Enum\UserRole;

final readonly class ModuleMenu
{
    public array $transactions;
    public array $statistics;
    public array $exports;
    public array $administrations;

    public function __construct(UserRole $role, array $accessibleModules)
    {
        $isAnyAdmin = $role->isAnyAdmin();
        $isSuperAdmin = $role->isSuperAdmin();

        $transactions = [];
        $statistics = [];
        $exports = [];
        $administrations = [];

        foreach ($accessibleModules as $accessibleModule) {
            $transactions[] = new ModuleMenuEntry(
                (string)$accessibleModule['name'],
                (string)$accessibleModule['menu_entry'],
            );

            $module = Module::fromId((int)$accessibleModule['id']);
            if ($module === null) {
                continue;
            }
            if ($module->hasStatisticsPage()) {
                $statistics[] = ModuleMenuEntry::fromModule($module);
            }
            if ($isAnyAdmin && $module->hasExportPage()) {
                $exports[] = ModuleMenuEntry::fromModule($module);
            }
            if ($isSuperAdmin && $module->hasAdministrationPage()) {
                $administrations[] = ModuleMenuEntry::fromModule($module);
            }
        }

        $this->transactions = $transactions;
        $this->statistics = $statistics;
        $this->exports = $exports;
        $this->administrations = $administrations;
    }
}
