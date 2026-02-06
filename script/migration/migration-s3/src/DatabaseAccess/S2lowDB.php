<?php

namespace App\DatabaseAccess;

use PDO;

class S2lowDB
{
    public function __construct(
        private readonly PDO $connexion
    ) {
    }
}
