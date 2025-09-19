<?php

namespace S2low\Factory;

use Doctrine\DBAL\Connection;
use PDO;

class PDOFactory
{
    private ?PDO $instance = null;

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function create(): \PDO
    {
        if ($this->instance === null) {
            $this->instance = $this->connection->getNativeConnection();
        }

        return $this->instance;
    }

    public function closeAll(): void
    {
        $this->connection->close();
        $this->instance = null;
    }
}
