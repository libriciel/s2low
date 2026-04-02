<?php

namespace App\Service;

use App\CloudAccess\OldS3;
use App\DatabaseAccess\SelfDB;
use App\DTO\MigrationItem;
use App\Enum\Status;

class DownloadTransaction
{
    public function __construct(
        private readonly SelfDB $selfDB,
        private readonly OldS3 $oldS3,
        private readonly string $tempDir = '/tmp'
    ) {
    }

    public function run(int $limit = 50, ?array $allowedTypes = null): void
    {
        $transactionsToHandle = $this->selfDB->getTransactionsByStatus(Status::BUCKET_FOUND, $limit, $allowedTypes);

        if (empty($transactionsToHandle)) {
            return;
        }

        foreach ($transactionsToHandle as $transaction) {
            $this->processDownload($transaction);
        }
    }

    public function processDownload(MigrationItem $transaction): void
    {
        $localPath = rtrim($this->tempDir, '/') . '/' . basename($transaction->oldKey) . '-' . $transaction->id;

        $bucket = $transaction->bucket;
        if (!$bucket) {
            echo "[DL] {$transaction->type} #{$transaction->id} ERROR: no bucket" . PHP_EOL;
            $this->selfDB->updateStatus($transaction, Status::ERROR, "No bucket assigned for download.");
            return;
        }

        $result = $this->oldS3->getFile($bucket, $transaction->oldKey, $localPath, true);
        $statusStr = $result['current_status'] ?? 'inconnu';
        $sizeMB = isset($result['size']) ? round($result['size'] / 1024 / 1024, 2) : '?';

        if ($statusStr === 'telechargé') {
            echo "[DL] {$transaction->type} #{$transaction->id} OK ({$sizeMB} MB)" . PHP_EOL;
            $this->selfDB->updateStatus($transaction, Status::DOWNLOADED);
        } elseif ($statusStr === 'en attente de restoration') {
            echo "[DL] {$transaction->type->value} #{$transaction->id} -> RESTORING (Glacier)" . PHP_EOL;
            $this->selfDB->updateStatus($transaction, Status::RESTORING);
        } elseif (str_starts_with($statusStr, 'erreur')) {
            echo "[DL] {$transaction->type} #{$transaction->id} ERROR: $statusStr" . PHP_EOL;
            $this->selfDB->updateStatus($transaction, Status::ERROR, $statusStr);
        } else {
            if ($statusStr === 'frozen') {
                echo "[DL] {$transaction->type} #{$transaction->id} -> RESTORING (frozen)" . PHP_EOL;
                $this->selfDB->updateStatus($transaction, Status::RESTORING);
            } else {
                echo "[DL] {$transaction->type} #{$transaction->id} ERROR: $statusStr" . PHP_EOL;
                $this->selfDB->updateStatus($transaction, Status::ERROR, "Unknown S3 status: $statusStr");
            }
        }
    }
}
