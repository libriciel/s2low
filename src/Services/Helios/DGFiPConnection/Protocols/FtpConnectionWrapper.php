<?php

namespace S2low\Services\Helios\DGFiPConnection\Protocols;

use FTP\Connection;

class FtpConnectionWrapper
{
    private Connection $connection;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return \FTP\Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }
}
