<?php

namespace App\Repository;

use App\PostgresDB;
use PDO;

abstract class AbstractRepository
{
    protected PDO $pdo;

    public function __construct()
    {
        $this->pdo = PostgresDB::getConnection();
    }
}
