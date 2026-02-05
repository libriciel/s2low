<?php

namespace App;

use App\CloudAccess\NewS3;
use App\CloudAccess\OldS3;
use App\DatabaseAccess\S2lowDB;
use App\DatabaseAccess\SelfDB;
use App\Repository\ActesRepository;
use App\Repository\HeliosRepository;
use App\Repository\MailSecRepository;

class MigrationOrchestrator
{
    public function __construct(
        private readonly NewS3 $newS3,
        private readonly OldS3 $oldS3,
        private readonly SelfDB $selfDBConnection,
        private readonly S2lowDB $s2lowDBConnexion,
        bool $dryRun = false
    ) {

    }

    public function runActes(): void
    {
        $repository = $this->getActesRepository();
        $this->processFlow('actes', $repository, function ($item) {
            $key = $item['file_path'];
            return [
                'id' => $item['id'],
                'key' => $key,
                'siren' => $item['siren']
            ];
        });
    }

    public function runHelios(): void
    {
        $repository = $this->getHeliosRepository();
        $this->processFlow('helios', $repository, function ($item) {
            $key = $item['siren'] . '/' . $item['sha1'];
            return [
                 'id' => $item['id'],
                 'key' => $key,
                 'siren' => $item['siren']
            ];
        });
    }

    public function runHeliosAcquit(): void
    {
        $repository = $this->getHeliosRepository();
        $this->processFlow('helios_acquit', $repository, function ($item) {
             $key = $item['siren'] . '/' . $item['acquit_filename'];
             return [
                 'id' => $item['id'],
                 'key' => $key,
                 'siren' => $item['siren']
             ];
        }, 'getAcquitBatch');
    }

    public function runMail(): void
    {
        $repository = $this->getMailSecRepository();
        $this->processFlow('mail', $repository, function ($item) {
            $key = $item['siren'] . '/' . $item['fn_download'] . '/mail.zip';
            return [
                 'id' => $item['id'],
                 'key' => $key,
                 'siren' => $item['siren']
            ];
        });
    }

    protected function getActesRepository(): ActesRepository
    {
        return new ActesRepository();
    }

    protected function getHeliosRepository(): HeliosRepository
    {
        return new HeliosRepository();
    }

    protected function getMailSecRepository(): MailSecRepository
    {
        return new MailSecRepository();
    }

    private function processBatch(string $type, array $batch, callable $mapItem): void
    {
        foreach ($batch as $data) {
            if ($this->stateTracker->isProcessed($type, $data['id'])) {
                continue;
            }

            $mapped = $mapItem($data);
            $key = $mapped['key'];
            $id = $mapped['id'];

            echo "[$type] Processing ID $id (Source Key: $key)... ";

            if ($this->dryRun) {
                // Pass $type to exists
                $sourceExists = $this->source->exists($key, $type);
                if ($sourceExists) {
                    echo "[DRY RUN] Found in $sourceExists. Would download and upload to NewS3." . PHP_EOL;
                    // Pass $type to exists
                    if ($this->newS3->exists($key, $type)) {
                         echo "[DRY RUN] WARNING: Already exists in NewS3." . PHP_EOL;
                    }
                } else {
                    echo "[DRY RUN] NOT FOUND in any source." . PHP_EOL;
                }
                continue;
            }

            $tempPath = sys_get_temp_dir() . '/s2low_migration_' . uniqid();

            // Pass $type to downloadFile
            if (!$this->source->downloadFile($key, true, $tempPath, $type)) {
                echo "FAILED to download from Source." . PHP_EOL;
                continue;
            }

            // Pass $type to upload
            if ($this->newS3->upload($key, $tempPath, $type)) {
                $this->stateTracker->markAsDone($type, $id);
                echo "DONE." . PHP_EOL;
            } else {
                echo "FAILED to upload to NewS3." . PHP_EOL;
            }

            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
        }
    }

    private function processFlow(string $type, $repository, callable $mapItem, string $batchMethod = 'getBatch'): void
    {
        $lastId = $this->stateTracker->getLastProcessedId($type);
        echo "Starting $type migration from ID $lastId..." . PHP_EOL;

        while (true) {
            $batch = $repository->$batchMethod($lastId, 100);
            if (empty($batch)) {
                break;
            }

            $this->processBatch($type, $batch, $mapItem);

            $lastItem = end($batch);
            $lastId = $lastItem['id'];
        }
        echo "Finished $type migration." . PHP_EOL;
    }

    public function checkCloudConnections(): void
    {
        echo "Checking Connections..." . PHP_EOL;
        $hasError = false;

        // Sources (OldS3 + OpenStack)
        if (!$this->source->checkConnection()) {
            // Error already printed by SourceStorage::checkConnection
            $hasError = true;
        }

        // Destination (NewS3)
        if (!$this->newS3->checkConnection()) {
             // Error already printed by NewS3::checkConnection
            $hasError = true;
        }

        if ($hasError) {
            echo "One or more connections failed." . PHP_EOL;
            exit(1);
        }

        echo "All systems ready." . PHP_EOL;
    }
}
