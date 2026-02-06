<?php

namespace App\DatabaseAccess;

use App\DTO\MigrationItem;
use App\Enum\Status;
use PDO;
use PDOException;

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

    public function getLastIdAtTypeAndStatus(string $type, Status $status): int
    {
        $stmt = $this->connexion->prepare("SELECT MAX(s2low_id) as last_id FROM transactions WHERE type = ? AND status = '" . $status->value . "'");
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

    public function create(MigrationItem $transaction): void
    {
        $sql = "INSERT INTO transactions (s2low_id, type, siren, key, date, status) VALUES (?, ?, ?, ?, ?, ?)";

        try {
            $this->connexion->prepare($sql)->execute([
                $transaction->id,
                $transaction->type,
                $transaction->siren,
                $transaction->key,
                $transaction->date,
                Status::HANDLE->value
            ]);
        } catch (PDOException $e) {
            echo 'error during transaction creation: ' . $e->getMessage();
            echo ';;'.json_encode($transaction).'!!';
        }
    }

    public function setUnfreeze(?MigrationItem $transaction)
    {
        
    }

    public function updateToError(?MigrationItem $transaction, string $getMessage)
    {

    }
}
