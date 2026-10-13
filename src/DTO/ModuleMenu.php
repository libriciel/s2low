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
        $knownModules = array_values(array_filter(array_map(
            static fn(array $module): ?Module => Module::fromId((int)$module['id']),
            $accessibleModules
        )));

        $this->transactions = $accessibleModules;
        $this->statistics = self::having($knownModules, static fn(Module $module): bool => $module->hasStatisticsPage());
        $this->exports = $role->isAnyAdmin()
            ? self::having($knownModules, static fn(Module $module): bool => $module->hasExportPage())
            : [];
        $this->administrations = $role->isSuperAdmin()
            ? self::having($knownModules, static fn(Module $module): bool => $module->hasAdministrationPage())
            : [];
    }

    private static function having(array $modules, callable $hasPage): array
    {
        return array_values(array_filter($modules, $hasPage));
    }
}
