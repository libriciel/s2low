<?php

namespace App\Factory;

use App\DatabaseAccess\SelfDB;
use PDO;

class ConnexionSelfDBFactory
{
    public static function getConnection($databaseFile): SelfDB
    {
        $dbFile = $databaseFile ?? (__DIR__ . '/../migration_db.sqlite');

        try {
            $dsn = "sqlite:" . $dbFile;

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];

            $connexion = new PDO($dsn, null, null, $options);

            $connexion->exec(
                "CREATE TABLE IF NOT EXISTS migration_status (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    type TEXT NOT NULL,
                    s2low_id INTEGER NOT NULL,
                    status TEXT NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE(type, s2low_id)
                );"
            );
        } catch (\PDOException $e) {
            // If not in CLI or if we want to bubble up
            throw new \Exception("Erreur SQLite : " . $e->getMessage());
        }

        return new SelfDB($connexion);
    }
}
