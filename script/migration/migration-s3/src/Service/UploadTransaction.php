<?php

namespace App\Service;

use App\CloudAccess\NewS3;
use App\DatabaseAccess\SelfDB;
use App\DTO\MigrationItem;
use App\Enum\Status;

class UploadTransaction
{
    public function __construct(
        private readonly SelfDB $selfDB,
        private readonly NewS3 $newS3,
        private readonly string $tempDir = '/tmp'
    ) {
    }

    public function run(int $limit = 50): void
    {
        $transactionsToUpload = $this->selfDB->getTransactionsByStatus(Status::DOWNLOADED, $limit);

        if (empty($transactionsToUpload)) {
            return;
        }

        foreach ($transactionsToUpload as $transaction) {
            $this->processUpload($transaction);
        }
    }

    public function processUpload(MigrationItem $transaction): void
    {
        $localPath = rtrim($this->tempDir, '/') . '/' . basename($transaction->key) . '-' . $transaction->id;

        if (!file_exists($localPath)) {
            echo "[UP] {$transaction->type} #{$transaction->id} ERROR: file missing" . PHP_EOL;
            $this->selfDB->updateStatus($transaction, Status::ERROR, "Local downloaded file is missing.");
            return;
        }

        $success = $this->newS3->upload($transaction->key, $localPath, $transaction->type);

        if ($success) {
            echo "[UP] {$transaction->type} #{$transaction->id} OK" . PHP_EOL;
            $this->selfDB->updateStatus($transaction, Status::COMPLETED);
            unlink($localPath);
        } else {
            echo "[UP] {$transaction->type} #{$transaction->id} ERROR: upload failed" . PHP_EOL;
            $this->selfDB->updateStatus($transaction, Status::ERROR, "Failed to upload to New S3");
        }
    }
}
