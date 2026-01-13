<?php

namespace App;

use PDO;
use Exception;

class PostgresDB
{
    private static ?PDO $connexion = null;

    public static function getConnection(): PDO
    {
        if (self::$connexion === null) {
            $host = $_ENV['POSTGRES_HOST'] ?? 'db';
            $dbName = $_ENV['POSTGRES_DB'] ?? 's2low';
            $user = $_ENV['POSTGRES_USER'] ?? 's2lowuser';
            $password = $_ENV['POSTGRES_PASSWORD'] ?? 's2lowpassword';
            $port = $_ENV['POSTGRES_PORT'] ?? '5432';

            $dsn = "pgsql:host=$host;port=$port;dbname=$dbName";

            if (!in_array('pgsql', PDO::getAvailableDrivers())) {
                echo "PostgreSQL Connection Error: Driver 'pgsql' not found." . PHP_EOL;
                echo "Available drivers: " . implode(', ', PDO::getAvailableDrivers()) . PHP_EOL;
                throw new Exception("PostgreSQL Connection Error: Driver 'pgsql' not found.");
            }

            try {
                self::$connexion = new PDO($dsn, $user, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            } catch (Exception $e) {
                // Remove die(), let caller handle it
                throw new Exception("PostgreSQL Connection Error: " . $e->getMessage());
            }
        }
        return self::$connexion;
    }
}
