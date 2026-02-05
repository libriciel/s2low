<?php

namespace App\Repository;

use ConnexionS2lowDBFactory;
use PDO;

abstract class AbstractRepository
{
    protected PDO $pdo;

    public function __construct()
    {
        $this->pdo = ConnexionS2lowDBFactory::getConnection();
    }
}
