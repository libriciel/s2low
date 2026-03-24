<?php

namespace App;

use App\CloudAccess\NewS3;
use App\CloudAccess\OldS3;
use App\DatabaseAccess\S2lowDB;
use App\DatabaseAccess\SelfDB;
use App\DTO\MigrationItem;
use App\Migration\ActesSource;
use App\Migration\MailSource;
use App\Migration\MigrationSourceInterface;
use App\Migration\PesAcquitSource;
use App\Migration\PesSource;
use App\Repository\ActesRepository;
use App\Repository\MailSecRepository;
use App\Repository\PesAcquitRepository;
use App\Repository\PesRepository;

class MigrationOrchestrator
{
    public function __construct(
        private readonly NewS3 $newS3,
        private readonly OldS3 $oldS3,
        private readonly SelfDB $selfDBConnection,
        private readonly S2lowDB $s2lowDBConnexion,
        private readonly bool $dryRun = false
    ) {
    }

    public function runActes(): void
    {
        $this->process(new ActesSource(new ActesRepository($this->s2lowDBConnexion)));
    }

    public function runHelios(): void
    {
        $this->process(new PesSource(new PesRepository($this->s2lowDBConnexion)));
    }

    public function runHeliosAcquit(): void
    {
        $this->process(new PesAcquitSource(new PesAcquitRepository($this->s2lowDBConnexion)));
    }

    public function runMail(): void
    {
        $this->process(new MailSource(new MailSecRepository($this->s2lowDBConnexion)));
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
            throw new \RuntimeException('Une connexion à échoué. Impossible de continuer.');
        }

        echo "All systems ready." . PHP_EOL;
    }

    private function process(MigrationSourceInterface $source): void
    {
        $type = $source->getIdentifier();
        $lastId = $this->selfDBConnection->getLastProcessedId($type);
        echo "Starting $type migration from ID $lastId..." . PHP_EOL;

        foreach ($source->getItems($lastId) as $item) {
            $this->processItem($type, $item);
        }

        echo "Finished $type migration." . PHP_EOL;
    }

    private function processItem(string $type, MigrationItem $item): void
    {
        $id = $item->id;
        $key = $item->key;

        echo "[$type] Processing ID $id (Source Key: $key)... ";

        if ($this->selfDBConnection->isProcessed($type, $id)) {
            echo "Skipping (already processed)." . PHP_EOL;
            return;
        }

        if ($this->dryRun) {
            echo "[DRY RUN] Would check existence, download and upload to NewS3." . PHP_EOL;
            if ($this->newS3->exists($key, $type)) {
                echo "[DRY RUN] WARNING: Already exists in NewS3." . PHP_EOL;
            }
            return;
        }

        $tempPath = sys_get_temp_dir() . '/s2low_migration_' . uniqid();

        // Assuming 'source-bucket' is a constant or config that was previously hardcoded
        $result = $this->oldS3->getFile('source-bucket', $key, $tempPath, true);

        if ($result['current_status'] === 'telechargé') {
            if ($this->newS3->upload($key, $tempPath, $type)) {
                $this->selfDBConnection->markAsDone($type, $id);
                echo "DONE." . PHP_EOL;
            } else {
                echo "FAILED to upload to NewS3." . PHP_EOL;
            }
        } else {
            echo "Status: " . $result['current_status'] . PHP_EOL;
        }

        if (file_exists($tempPath)) {
            unlink($tempPath);
        }
    }
}
