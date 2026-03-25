<?php

namespace App\Service;

use App\CloudAccess\OldS3;
use App\DatabaseAccess\SelfDB;
use App\DTO\MigrationItem;
use App\Enum\Status;

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
            if ($this->oldS3->exists($bucketName, $transaction->key)) {
                echo "[RESOLVE] {$transaction->type} #{$transaction->id} -> {$bucketName}" . PHP_EOL;
                $this->selfDB->updateBucket($transaction, $bucketName);
                $this->selfDB->updateStatus($transaction, Status::BUCKET_FOUND);
                return true;
            }
        }

        echo "[RESOLVE] {$transaction->type} #{$transaction->id} -> NOT FOUND" . PHP_EOL;
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
            \App\Enum\Type::ACTE->value => $this->getActesBuckets($year),
            \App\Enum\Type::PES_ALLER->value => $this->getPesAllerBuckets($transaction->key),
            \App\Enum\Type::PES_ACQUIT->value => ['sladullact-helios-pesacquitprefix'],
            \App\Enum\Type::MAIL->value => ['sladullact-mail'],
            default => ['sladullact-actes']
        };
    }

    private function getActesBuckets(string $year): array
    {
        $primaryBucket = ($year === '2007') ? 'sl-adullact-actes2007' : "sl-adullact-actes-{$year}";
        $buckets = [$primaryBucket, 'sladullact-actes'];

        for ($y = (int)date('Y'); $y >= 2008; $y--) {
            $b = "sl-adullact-actes-{$y}";
            if (!in_array($b, $buckets)) {
                $buckets[] = $b;
            }
        }
        if (!in_array('sl-adullact-actes2007', $buckets)) {
            $buckets[] = 'sl-adullact-actes2007';
        }

        return $buckets;
    }

    private function getPesAllerBuckets(string $key): array
    {
        $allHexChars = ['0','1','2','3','4','5','6','7','8','9','a','b','c','d','e','f'];
        $prefix = 'sladullact-helios-aller-file';

        $parts = explode('/', $key);
        $sha1 = end($parts);
        $firstChar = strtolower(substr($sha1, 0, 1));

        $buckets = ["{$prefix}{$firstChar}"];
        foreach ($allHexChars as $hex) {
            if ($hex !== $firstChar) {
                $buckets[] = "{$prefix}{$hex}";
            }
        }

        return $buckets;
    }
}
