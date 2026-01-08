<?php

namespace App;

use PDO;

class SQLite {
    static private ?PDO $connexion = null;
    static function getConnection() {
        if (self::$connexion === null) {
            $databaseFile = 'ma_base_donnees.sqlite';

            try {
                $dsn = "sqlite:" . $databaseFile;

                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ];

                self::$connexion = new PDO($dsn, null, null, $options);

            } catch (\PDOException $e) {
                die("Erreur SQLite : " . $e->getMessage());
            }
        }

        return self::$connexion;
    }

    static function addTransaction(string $typeTransaction, int $identifiantS2low, string $etat, string $source): bool
    {
        $res = self::getConnection()
            ->prepare("INSERT INTO transactions (type_transaction, identifiant_s2low, etat, source) VALUES (?, ?, ?, ?)")
        ->execute([$typeTransaction, $identifiantS2low, $etat, $source]);

        return $res !== false;
    }
}

