<?php

namespace App\Service;

use App\CloudAccess\OldS3;
use App\DatabaseAccess\SelfDB;
use App\DTO\MigrationItem;
use App\Enum\Status;

class GlacierRestoreChecker
{
    public function __construct(
        private readonly SelfDB $selfDB,
        private readonly OldS3 $oldS3
    ) {
    }

    public function run(int $limit = 50, ?array $allowedTypes = null): void
    {
        $transactionsToHandle = $this->selfDB->getTransactionsByStatus(Status::RESTORING, $limit, $allowedTypes);

        if (empty($transactionsToHandle)) {
            return;
        }

        $ready = 0;
        $ongoing = 0;
        $errors = 0;

        foreach ($transactionsToHandle as $transaction) {
            $result = $this->checkRestoration($transaction);
            match ($result) {
                'ready' => $ready++,
                'ongoing' => $ongoing++,
                default => $errors++,
            };
        }

        echo "[GLACIER] Checked " . count($transactionsToHandle) . ": {$ready} ready, {$ongoing} ongoing, {$errors} errors" . PHP_EOL;
    }

    public function checkRestoration(MigrationItem $transaction): string
    {
        $bucket = $transaction->bucket;
        if (!$bucket) {
            $this->selfDB->updateStatus($transaction, Status::ERROR, "Missing bucket for RESTORING item");
            return 'error';
        }

        $result = $this->checkS3Status($bucket, $transaction->oldKey);

        if ($result === 'ready') {
            $this->selfDB->updateStatus($transaction, Status::BUCKET_FOUND);
            return 'ready';
        } elseif ($result === 'ongoing') {
            return 'ongoing';
        } else {
            if (str_starts_with($result, 'erreur')) {
                $this->selfDB->updateStatus($transaction, Status::ERROR, $result);
            }
            return 'error';
        }
    }

    private function checkS3Status(string $bucket, string $key): string
    {
        try {
            $meta = $this->oldS3->getS3Client()->headObject([
                'Bucket' => $bucket,
                'Key'    => $key,
            ]);

            $storageClass = $meta['StorageClass'] ?? 'STANDARD';
            $restoreHeader = $meta['Restore'] ?? '';

            if (!in_array($storageClass, ['GLACIER', 'DEEP_ARCHIVE'])) {
                return 'ready';
            }

            if (empty($restoreHeader)) {
                return 'frozen';
            }

            if (str_contains($restoreHeader, 'ongoing-request="true"')) {
                return 'ongoing';
            }

            return 'ready';

        } catch (\Exception $e) {
            return 'erreur: ' . $e->getMessage();
        }
    }
}
