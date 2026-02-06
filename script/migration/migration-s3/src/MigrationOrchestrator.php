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
//        foreach ($batch as $data) {
//            if ($this->stateTracker->isProcessed($type, $data['id'])) {
//                continue;
//            }
//
//            $mapped = $mapItem($data);
//            $key = $mapped['key'];
//            $id = $mapped['id'];
//
//            echo "[$type] Processing ID $id (Source Key: $key)... ";
//
//            if ($this->dryRun) {
//                // In OldS3.php, checkConnection uses headObject.
//                // We'll assume successful check if no exception is thrown or use a simulated check.
//                echo "[DRY RUN] Would check existence, download and upload to NewS3." . PHP_EOL;
//                if ($this->newS3->exists($key, $type)) {
//                     echo "[DRY RUN] WARNING: Already exists in NewS3." . PHP_EOL;
//                }
//                continue;
//            }
//
//            $tempPath = sys_get_temp_dir() . '/s2low_migration_' . uniqid();
//
//            $result = $this->oldS3->getFile('source-bucket', $key, $tempPath, true);
//
//            if ($result['current_status'] === 'telechargé') {
//                if ($this->newS3->upload($key, $tempPath, $type)) {
//                    $this->selfDBConnection->markAsDone($type, $id);
//                    echo "DONE." . PHP_EOL;
//                } else {
//                    echo "FAILED to upload to NewS3." . PHP_EOL;
//                }
//            } else {
//                echo "Status: " . $result['current_status'] . PHP_EOL;
//            }
//
//            if (file_exists($tempPath)) {
//                unlink($tempPath);
//            }
//        }
    }

    private function processFlow(string $type, $repository, callable $mapItem, string $batchMethod = 'getBatch'): void
    {
//        $lastId = $this->selfDBConnection->getLastProcessedId($type);
//        echo "Starting $type migration from ID $lastId..." . PHP_EOL;
//
//        while (true) {
//            $batch = $repository->$batchMethod($lastId, 100);
//            if (empty($batch)) {
//                break;
//            }
//
//            $this->processBatch($type, $batch, $mapItem);
//
//            $lastItem = end($batch);
//            $lastId = $lastItem['id'];
//        }
//        echo "Finished $type migration." . PHP_EOL;
    }

    public function checkCloudConnections(): void
    {
        $hasError = false;
        if (!$this->oldS3->checkConnection()) {
            $hasError = true;
        }

        if (!$this->newS3->checkConnection()) {
            $hasError = true;
        }

        if ($hasError) {
            echo "One or more connections failed." . PHP_EOL;
            exit(1);
        }

        echo "All systems ready." . PHP_EOL;
    }
}
