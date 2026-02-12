<?php

namespace S2low\Factory;

use PDO;

class PDOFactory
{
    private const DATABASE_TYPE = "pgsql";
    private const CLIENT_ENCODING_DEFAULT = "UTF-8";
    private array $instances = [];

    public function __construct(
        private readonly string $hostname,
        private readonly string $name,
        private readonly string $login,
        private readonly string $password,
    ) {
    }

    public function create(): \PDO
    {
        $dsn = self::DATABASE_TYPE . ":host=" . $this->hostname;
        if ($this->name) {
            $dsn .= ";dbname=" . $this->name;
        }
        $pdo = new PDO($dsn, $this->login, $this->password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->query("SET CLIENT_ENCODING TO '" . self::CLIENT_ENCODING_DEFAULT . "';");
        $pdo->query("SET standard_conforming_strings = off;");

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
