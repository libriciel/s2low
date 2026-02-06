<?php

namespace App\Factory;

use App\DatabaseAccess\SelfDB;
use PDO;

class ConnexionSelfDBFactory
{
    public static function getConnection($host, $db, $user, $pass, $port): SelfDB
    {
        try {
            $dsn = "pgsql:host=$host;port=$port;dbname=$db;";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];

            $connexion = new PDO($dsn, $user, $pass, $options);

            $connexion->exec(
                "CREATE TABLE IF NOT EXISTS transactions (
                id SERIAL PRIMARY KEY,
                s2low_id INTEGER NOT NULL,
                type TEXT NOT NULL CHECK(type IN ('ACTE', 'PES_ALLER', 'PES_ACQUIT', 'MAIL')),
                bucket TEXT NOT NULL,
                key TEXT NOT NULL,
                status TEXT NOT NULL CHECK(status IN ('HANDLE', 'ASK', 'DOWNLOADED', 'COMPLETED', 'ERROR')),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(s2low_id))
                ");
        } catch (\PDOException $e) {
            throw new \Exception("Erreur SQLite : " . $e->getMessage());
        }

        return new SelfDB($connexion);
    }
}
