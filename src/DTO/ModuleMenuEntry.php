<?php

declare(strict_types=1);

namespace S2low\DTO;

use S2low\Enum\Module;

final readonly class ModuleMenuEntry
{
    public function __construct(
        public string $name,
        public string $label,
    ) {
    }

    public static function fromModule(Module $module): self
    {
        return new self($module->name(), $module->name());
    }
}
