<?php

namespace S2low\Factory;

use Doctrine\DBAL\Connection;
use PDO;

class PDOFactory
{
    private const DATABASE_TYPE = "pgsql";
    private const CLIENT_ENCODING_DEFAULT = "UTF-8";
    private array $instances = [];

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function create(): \PDO
    {
        $pdo = $this->connection->getNativeConnection();
//        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//        $pdo->query("SET CLIENT_ENCODING TO '" . self::CLIENT_ENCODING_DEFAULT . "';");
//        $pdo->query("SET standard_conforming_strings = off;");

        $this->instances[] = $pdo;

        return $pdo;
    }

    public function closeAll(): void
    {
        foreach ($this->instances as &$pdo) {
            $pdo = null;
        }
    }
}
