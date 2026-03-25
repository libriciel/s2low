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

    public function run(int $limit = 50): void
    {
        // On récupère soit les HANDLE (Jamais touchés), soit les ASK (En attente de restore Glacier)
        $transactionsToHandle = array_merge(
            $this->selfDB->getTransactionsByStatus(Status::HANDLE, $limit),
            $this->selfDB->getTransactionsByStatus(Status::ASK, $limit)
        );

        echo ">>> [DownloadTransaction] Found " . count($transactionsToHandle) . " transactions to process." . PHP_EOL;

        foreach ($transactionsToHandle as $transaction) {
            $this->processDownload($transaction);
        }
    }

    public function processDownload(MigrationItem $transaction): void
    {
        echo "Processing download for {$transaction->type} ID {$transaction->id} (Key: {$transaction->key})... ";

        $localPath = rtrim($this->tempDir, '/') . '/' . basename($transaction->key) . '-' . $transaction->id;

        // "source-bucket" est un hardcode dans le code existant ou peut être dynamique.
        // Remarque: Dans OldS3->checkConnection(), le bucket est sl-adullact-actes-2019 par exemple.
        // On utilise ici une logique simple, idéalement le bucket devrait venir de la conf.
        // On l'extrait de $_ENV ou d'un fallback.
        $bucket = $_ENV['OLD_S3_BUCKET_NAME'] ?? 'sl-adullact-actes-2019'; 

        $result = $this->oldS3->getFile($bucket, $transaction->key, $localPath, true);

        $statusStr = $result['current_status'] ?? 'inconnu';

        if ($statusStr === 'telechargé') {
            echo "DONE." . PHP_EOL;
            $this->selfDB->updateStatus($transaction, Status::DOWNLOADED);
        } elseif ($statusStr === 'en attente de restoration') {
            echo "ASKED RESTORE (Glacier)." . PHP_EOL;
            $this->selfDB->updateStatus($transaction, Status::ASK);
        } elseif (str_starts_with($statusStr, 'erreur')) {
            echo "ERROR: $statusStr" . PHP_EOL;
            $this->selfDB->updateStatus($transaction, Status::ERROR, $statusStr);
        } else {
            echo "STATUS: $statusStr" . PHP_EOL;
            // Si on ne sait pas quoi faire, on met ERROR pour éviter une boucle infinie
            if ($statusStr === 'frozen') {
                 // Si autoRestore était à false et c'est gelé (normalement c'est à true ici)
                 $this->selfDB->updateStatus($transaction, Status::ASK);
            } else {
                 $this->selfDB->updateStatus($transaction, Status::ERROR, "Unknown S3 status: $statusStr");
            }
        }
    }
}
