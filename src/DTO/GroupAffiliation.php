<?php

declare(strict_types=1);

namespace S2low\DTO;

use S2low\Enum\AdministeredModule;

final readonly class GroupAffiliation
{
    /**
     * @param list<AdministeredModule> $modules
     */
    public function __construct(
        public string $groupName,
        public array $modules
    ) {
    }

    public function getCaption(): string
    {
        if ($this->modules === []) {
            return 'Groupe';
        }

        $labels = array_map(static fn(AdministeredModule $module): string => $module->label(), $this->modules);

        return 'Groupe ' . implode(' / ', $labels);
    }
}
