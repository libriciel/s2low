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

        echo ">>> [UploadTransaction] Found " . count($transactionsToUpload) . " transactions ready to upload." . PHP_EOL;

        foreach ($transactionsToUpload as $transaction) {
            $this->processUpload($transaction);
        }
    }

    public function processUpload(MigrationItem $transaction): void
    {
        echo "Processing upload for {$transaction->type} ID {$transaction->id} (Key: {$transaction->key})... ";

        $localPath = rtrim($this->tempDir, '/') . '/' . basename($transaction->key) . '-' . $transaction->id;

        if (!file_exists($localPath)) {
            echo "FAILED (Local file missing: $localPath)." . PHP_EOL;
            $this->selfDB->updateStatus($transaction, Status::ERROR, "Local downloaded file is missing.");
            return;
        }

        // Le upload vers le nouveau bucket selon le type.
        // NewS3 semble s'attendre au key, tmpPath et type
        $success = $this->newS3->upload($transaction->key, $localPath, $transaction->type);

        if ($success) {
            echo "UPLOADED." . PHP_EOL;
            
            // Marquer dans Self DB comme COMPLETED
            $this->selfDB->updateStatus($transaction, Status::COMPLETED);
            
            // Note: on utilise aussi markAsDone pour historiser dans migration_status si désiré.
            $this->selfDB->markAsDone($transaction->type, $transaction->id);

            // Supprimer le fichier temporaire
            unlink($localPath);
        } else {
            echo "UPLOAD FAILED." . PHP_EOL;
            $this->selfDB->updateStatus($transaction, Status::ERROR, "Failed to upload to New S3");
        }
    }
}
