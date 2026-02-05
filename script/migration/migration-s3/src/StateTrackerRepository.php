<?php

namespace App;

use PDO;

/**
 * @description Cette classe sert a enregistrer l'etat d'avancement des transaction et a pouvoir reprendre ou on en ete en cas d'interruption de l'execution
 */
class StateTrackerRepository implements StateTrackerInterface
{
    private ?PDO $connexion = null;
    private ?string $databaseFile;

    public function __construct(?string $databaseFile = null)
    {
        $this->databaseFile = $databaseFile;
    }

    public function getConnection(): PDO
    {
        if ($this->connexion === null) {
            $dbFile = $this->databaseFile ?? (__DIR__ . '/../migration_db.sqlite');

            try {
                $dsn = "sqlite:" . $dbFile;

                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ];

                $this->connexion = new PDO($dsn, null, null, $options);

                $this->connexion->exec(
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
        }

        return $this->connexion;
    }

    public function getLastProcessedId(string $type): int
    {
        $stmt = $this->getConnection()->prepare("SELECT MAX(s2low_id) as last_id FROM migration_status WHERE type = ? AND status = 'done'");
        $stmt->execute([$type]);
        $result = $stmt->fetch();
        return $result['last_id'] ?? 0;
    }

    public function isProcessed(string $type, int $s2lowId): bool
    {
        $stmt = $this->getConnection()->prepare("SELECT 1 FROM migration_status WHERE type = ? AND s2low_id = ? AND status = 'done'");
        $stmt->execute([$type, $s2lowId]);
        return (bool)$stmt->fetch();
    }

    public function markAsDone(string $type, int $s2lowId): void
    {
        $sql = "INSERT INTO migration_status (type, s2low_id, status) VALUES (?, ?, 'done') 
                ON CONFLICT(type, s2low_id) DO UPDATE SET status = 'done', created_at = CURRENT_TIMESTAMP";
        $this->getConnection()->prepare($sql)->execute([$type, $s2lowId]);
    }
}
