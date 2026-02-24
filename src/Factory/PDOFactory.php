<?php

namespace S2low\Factory;

use Doctrine\DBAL\Connection;

class PDOFactory
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function create(): \PDO
    {
        $nativeConnection = $this->connection->getNativeConnection();

        if (!$nativeConnection instanceof \PDO) {
            throw new \LogicException('Le driver DBAL configuré ne renvoie pas une instance de PDO.');
        }

        return $nativeConnection;
    }
    
    public function closeAll(): void
    {
        $this->connection->close();
    }
}
