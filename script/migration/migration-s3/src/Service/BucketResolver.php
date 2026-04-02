<?php

namespace App\Service;

use App\CloudAccess\OldS3;
use App\DatabaseAccess\SelfDB;
use App\DTO\MigrationItem;
use App\Enum\Status;
use App\Enum\Type;

class BucketResolver
{
    public function __construct(
        private readonly SelfDB $selfDB,
        private readonly OldS3 $oldS3
    ) {
    }

    public function run(int $limit = 50): void
    {
        $transactionsToResolve = $this->selfDB->getTransactionsByStatus(Status::HANDLE, $limit);

        if (empty($transactionsToResolve)) {
            return;
        }

        foreach ($transactionsToResolve as $transaction) {
            $this->processBucketResolve($transaction);
        }
    }

    public function processBucketResolve(MigrationItem $transaction): bool
    {
        $bucketsToTest = $this->getBucketsByPriority($transaction);

        foreach ($bucketsToTest as $bucketName) {
            if ($this->oldS3->exists($bucketName, $transaction->oldKey)) {
                echo "[RESOLVE] {$transaction->type->value} #{$transaction->id} -> {$bucketName}" . PHP_EOL;
                $this->selfDB->updateBucket($transaction, $bucketName);
                $this->selfDB->updateStatus($transaction, Status::BUCKET_FOUND);
                return true;
            }
        }

        echo "[RESOLVE] {$transaction->type->value} #{$transaction->id} -> NOT FOUND" . PHP_EOL;
        $this->selfDB->updateStatus($transaction, Status::ERROR, "File not found in any S3 bucket array tests.");
        return false;
    }

    /**
     * Retourne la liste ordonnée des buckets à tester pour une transaction donnée.
     */
    private function getBucketsByPriority(MigrationItem $transaction): array
    {
        $year = substr($transaction->date, 0, 4);

        return match ($transaction->type) {
            // ---- ACTES ----
            // On teste le bucket de l'année métier. 
            // Si on est en Jan/Feb, on teste aussi N-1.
            // Si on est en Nov/Dec, on teste aussi N+1.
            // Puis fallback global sladullact-actes.
            Type::ACTE => $this->getActesBuckets($transaction->date),
            Type::PES_ALLER => $this->getPesAllerBuckets($transaction->oldKey),
            Type::PES_ACQUIT => ['sladullact-helios-pesacquitprefix'],
            Type::MAIL => ['sladullact-mail'],
            default => ['sladullact-actes']
        };
    }

    /**
     * Construit la liste optimisée des buckets Actes selon le mois.
     */
    private function getActesBuckets(string $date): array
    {
        $year = (int)substr($date, 0, 4);
        $month = (int)substr($date, 5, 2);

        $buckets = [$this->getYearlyBucket($year)];

        if ($month <= 2) {
             $buckets[] = $this->getYearlyBucket($year - 1);
        } elseif ($month >= 11) {
             $buckets[] = $this->getYearlyBucket($year + 1);
        }

        $buckets[] = 'sladullact-actes';

        return array_unique($buckets);
    }

    private function getYearlyBucket(int $year): string
    {
        if ($year == 2007) return 'sl-adullact-actes2007';
        return "sl-adullact-actes-{$year}";
    }

    private function getPesAllerBuckets(string $key): array
    {
        $prefix = 'sladullact-helios-aller-file';

        $parts = explode('/', $key);
        $sha1 = end($parts);
        $firstChar = strtolower(substr($sha1, 0, 1));

        $buckets = ["{$prefix}{$firstChar}"];

        return $buckets;
    }
}
