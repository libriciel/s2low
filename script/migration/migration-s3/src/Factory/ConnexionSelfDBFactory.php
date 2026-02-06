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
                type TEXT NOT NULL CHECK(type IN ('" . Type::ACTE->value . "', '" . Type::PES_ALLER->value . "', '" . Type::PES_ACQUIT->value . "', '" . Type::MAIL->value . "')),
                date TIMESTAMP NOT NULL,
                siren TEXT NOT NULL,
                key TEXT NOT NULL,
                status TEXT NOT NULL CHECK(status IN ('" . Status::HANDLE->value . "', '" . Status::ASK->value . "', '" . Status::DOWNLOADED->value . "', '" . Status::COMPLETED->value . "', '" . Status::ERROR->value . "')),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)
                ");
        } catch (\PDOException $e) {
            throw new \Exception("Erreur SQLite : " . $e->getMessage());
        }

        return new SelfDB($connexion);
    }
}
