<?php

namespace S2lowLegacy\Class;

class UserContext
{
    public function __construct(
        public readonly Connexion $connexion,
        public readonly ?User $me,
        public readonly array $userInfo,
        public readonly array $authorityInfo,
        public readonly ?array $groupeInfo
    ) {
    }
}
