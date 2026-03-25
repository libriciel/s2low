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

        if (!empty($transactionsToResolve)) {
            echo ">>> [BucketResolver] Found " . count($transactionsToResolve) . " transactions to resolve bucket." . PHP_EOL;
            foreach ($transactionsToResolve as $transaction) {
                $this->processBucketResolve($transaction);
            }
        }
    }

    public function processBucketResolve(MigrationItem $transaction): bool
    {
        echo "Resolving bucket for {$transaction->type} ID {$transaction->id}... ";

        $bucketsToTest = $this->getBucketsByPriority($transaction);

        foreach ($bucketsToTest as $bucketName) {
            // Requête S3 (HeadObject) pour tester si la clé existe dans ce bucket
            if ($this->oldS3->exists($bucketName, $transaction->key)) {
                echo "FOUND in {$bucketName}" . PHP_EOL;
                
                // Enregistrer le bucket dans la BDD
                $this->selfDB->updateBucket($transaction, $bucketName);
                
                // Avancer le statut à BUCKET_FOUND (prêt pour Download)
                $this->selfDB->updateStatus($transaction, Status::BUCKET_FOUND);
                return true;
            }
        }

        echo "NOT FOUND in any tested bucket." . PHP_EOL;
        // Si introuvable nulle part, on le met en erreur pour ne pas bloquer le daemon.
        $this->selfDB->updateStatus($transaction, Status::ERROR, "File not found in any S3 bucket array tests.");
        return false;
    }

    /**
     * Retourne la liste ordonnée des buckets à tester pour une transaction donnée.
     *
     * ACTES : sl-adullact-actes-{YYYY} (2008-2026), sl-adullact-actes{YYYY} (2007, pas de tiret), sladullact-actes (fallback)
     * PES ALLER : sladullact-helios-aller-file{X} où X = 1er caractère hex du sha1 (sharding 0-f)
     * PES ACQUIT : sladullact-helios-pesacquitprefix (bucket unique)
     * MAIL : pas de bucket S3 connu pour l'instant (à configurer)
     */
    private function getBucketsByPriority(MigrationItem $transaction): array
    {
        $year = substr($transaction->date, 0, 4);

        return match ($transaction->type) {

            // ---- ACTES ----
            // Priorité 1 : bucket de l'année métier (avec ou sans tiret pour 2007)
            // Priorité 2 : bucket global (sladullact-actes)
            // Priorité 3 : tous les autres buckets par année en fallback
            Type::ACTE->value => $this->getActesBuckets($year),

            // ---- PES ALLER ----
            // Sharding par 1er caractère hex de la clé (sha1).
            // Key = "siren/sha1", le sha1 est après le "/"
            // Priorité 1 : bucket calculé (sladullact-helios-aller-file{X})
            // Puis fallback : tous les autres buckets hex
            Type::PES_ALLER->value => $this->getPesAllerBuckets($transaction->key),

            // ---- PES ACQUIT ----
            // Bucket unique
            Type::PES_ACQUIT->value => [
                'sladullact-helios-pesacquitprefix',
            ],

            // ---- MAIL ----
            // À adapter quand les buckets mail seront connus
            Type::MAIL->value => [
                'sladullact-mail',
            ],

            default => [
                'sladullact-actes',
            ]
        };
    }

    /**
     * Construit la liste des buckets Actes.
     * 1. Bucket de l'année métier.
     * 2. Bucket global sladullact-actes.
     * 3. Tous les autres buckets par année en fallback.
     */
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

    /**
     * Construit la liste des buckets PES Aller ordonnée par probabilité.
     * Le 1er caractère hex du sha1 (après le siren/) détermine le bucket prioritaire.
     * On le place en tête, puis tous les autres en fallback.
     *
     * Key format: "siren/sha1_hash"
     * Bucket format: "sladullact-helios-aller-file{0-9a-f}"
     */
    private function getPesAllerBuckets(string $key): array
    {
        $allHexChars = ['0','1','2','3','4','5','6','7','8','9','a','b','c','d','e','f'];
        $prefix = 'sladullact-helios-aller-file';

        // Extraire le sha1 : la partie après le dernier "/"
        $parts = explode('/', $key);
        $sha1 = end($parts);

        // Premier caractère hex du sha1
        $firstChar = strtolower(substr($sha1, 0, 1));

        // Construire la liste : bucket prioritaire en premier, puis les autres
        $buckets = ["{$prefix}{$firstChar}"];

        foreach ($allHexChars as $hex) {
            if ($hex !== $firstChar) {
                $buckets[] = "{$prefix}{$hex}";
            }
        }

        return $buckets;
    }
}

