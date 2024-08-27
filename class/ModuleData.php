<?php

namespace S2lowLegacy\Class;

class ModuleData
{
    public function __construct(
        public array $moduleInfo,
        public string $permUser,
        public array $modulesInfo,
        public string $module_name
    ) {
    }
}
