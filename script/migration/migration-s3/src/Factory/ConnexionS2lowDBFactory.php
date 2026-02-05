<?php

namespace App\Factory;

use App\DatabaseAccess\S2lowDB;
use Exception;
use PDO;

class ConnexionS2lowDBFactory
{
    public static function getConnection($config): S2lowDB
    {
        $host = $config['POSTGRES_HOST'] ?? 'db';
        $dbName = $config['POSTGRES_DB'] ?? 's2low';
        $user = $config['POSTGRES_USER'] ?? 's2lowuser';
        $password = $config['POSTGRES_PASSWORD'] ?? 's2lowpassword';
        $port = $config['POSTGRES_PORT'] ?? '5432';

        $dsn = "pgsql:host=$host;port=$port;dbname=$dbName";

        if (!in_array('pgsql', PDO::getAvailableDrivers())) {
            echo "PostgreSQL Connection Error: Driver 'pgsql' not found." . PHP_EOL;
            echo "Available drivers: " . implode(', ', PDO::getAvailableDrivers()) . PHP_EOL;
            throw new Exception("PostgreSQL Connection Error: Driver 'pgsql' not found.");
        }

        try {
            $connexion = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (Exception $e) {
            // Remove die(), let caller handle it
            throw new Exception("PostgreSQL Connection Error: " . $e->getMessage());
        }

        return new S2lowDB($connexion);
    }
}
