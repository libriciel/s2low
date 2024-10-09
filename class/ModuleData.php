<?php

namespace S2lowLegacy\Class;

class ModuleData
{
    public function __construct(
        public readonly array $moduleInfo,
        public readonly string $permUser,
        public readonly array $modulesInfo,
        public readonly string $module_name
    ) {
    }
}
