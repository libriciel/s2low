<?php

namespace App\Factory;

use App\DatabaseAccess\SelfDB;
use App\Enum\Status;
use App\Enum\Type;
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
                type TEXT NOT NULL CHECK(type IN ('" . Type::ACTE->value . "', '" . Type::PES_ALLER->value . "', '" . Type::PES_ACQUIT->value . "', '" . Type::PES_RETOUR->value . "', '" . Type::MAIL->value . "')),
                date TIMESTAMP NOT NULL,
                oldKey TEXT,
                newKey TEXT NOT NULL,
                bucket TEXT,
                status TEXT NOT NULL CHECK(status IN ('" . Status::HANDLE->value . "', '" . Status::BUCKET_FOUND->value . "', '" . Status::ASK->value . "', '" . Status::RESTORING->value . "', '" . Status::DOWNLOADED->value . "', '" . Status::COMPLETED->value . "', '" . Status::ERROR_KEY_NULL->value . "', '" . Status::ERROR->value . "')),
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(type, s2low_id))
                ");

            // Indexes for 50M+ scale performance
            $connexion->exec("CREATE INDEX IF NOT EXISTS idx_transactions_status_type ON transactions(status, type)");
            $connexion->exec("CREATE INDEX IF NOT EXISTS idx_transactions_type_s2low_id ON transactions(type, s2low_id)");
        } catch (\PDOException $e) {
            throw new \Exception("Erreur PostgreSQL : " . $e->getMessage());
        }

        return new SelfDB($connexion);
    }
}
