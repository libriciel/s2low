<?php

namespace S2lowLegacy\Class;

class InitData
{
    public function __construct(
        public Connexion $connexion,
        public ?User $me,
        public $userInfo,
        public $authorityInfo,
        public $groupeInfo
    ) {
    }
}
