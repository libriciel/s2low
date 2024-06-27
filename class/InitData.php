<?php

namespace S2lowLegacy\Class;

class InitData
{
    public function __construct(
        public Connexion $connexion,
        public ?User $me,
        public $userInfo,
        public $authorityInfo,
        public $groupeInfo,
        public $moduleInfo = [],
        public $permUser = [],
        public $droit_specific = [],
        public $modulesInfo = [],
        public string $module_name = ''
    ) {
    }
}
