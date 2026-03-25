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
        $stmt = $this->connexion->prepare("SELECT 1 FROM migration_status WHERE type = ? AND s2low_id = ? AND status = " . Status::COMPLETED->value);
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

    public function getTransactionsByStatus(Status $status, int $limit = 100): array
    {
        $stmt = $this->connexion->prepare("SELECT s2low_id as id, type, siren, key, date FROM transactions WHERE status = ? LIMIT ?");
        $stmt->execute([$status->value, $limit]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $items = [];
        foreach ($rows as $row) {
            $items[] = new MigrationItem(
                id: (int)$row['id'],
                key: $row['key'],
                type: $row['type'],
                date: $row['date'],
                siren: $row['siren']
            );
        }
        return $items;
    }

    public function updateStatus(MigrationItem $transaction, Status $status, ?string $message = null): void
    {
        // On SQLite, you might want to log the message as well if there's a column for it,
        // but for now we update the status.
        $sql = "UPDATE transactions SET status = ? WHERE s2low_id = ? AND type = ?";
        $this->connexion->prepare($sql)->execute([
            $status->value,
            $transaction->id,
            $transaction->type
        ]);
        
        // Log error message if needed (we assume a basic implementation without changing schema)
        if ($message && $status === Status::ERROR) {
            echo "[ERROR] Transaction {$transaction->type}-{$transaction->id}: $message\n";
        }
    }

    // Garde ces méthodes pour la rétrocompatibilité avec UnfreezeFile si nécessaire
    public function setUnfreeze(MigrationItem $transaction): void
    {
        $this->updateStatus($transaction, Status::ASK);
    }

    public function updateToError(MigrationItem $transaction, string $getMessage): void
    {
        $this->updateStatus($transaction, Status::ERROR, $getMessage);
    }
}
