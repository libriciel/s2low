<?php

namespace App\DatabaseAccess;

use PDO;

class SelfDB
{
    public function __construct(
        private readonly PDO $connexion
    ) {
    }

    public function getPDO(): PDO
    {
        return $this->connexion;
    }

    public function getLastProcessedId(string $type): int
    {
        $stmt = $this->connexion->prepare("SELECT MAX(s2low_id) as last_id FROM migration_status WHERE type = ? AND status = 'done'");
        $stmt->execute([$type]);
        $result = $stmt->fetch();
        return $result['last_id'] ?? 0;
    }

    public function isProcessed(string $type, int $s2lowId): bool
    {
        $stmt = $this->connexion->prepare("SELECT 1 FROM migration_status WHERE type = ? AND s2low_id = ? AND status = 'done'");
        $stmt->execute([$type, $s2lowId]);
        return (bool)$stmt->fetch();
    }

    public function markAsDone(string $type, int $s2lowId): void
    {
        $sql = "INSERT INTO migration_status (type, s2low_id, status) VALUES (?, ?, 'done') 
                ON CONFLICT(type, s2low_id) DO UPDATE SET status = 'done', created_at = CURRENT_TIMESTAMP";
        $this->connexion->prepare($sql)->execute([$type, $s2lowId]);
    }
}
