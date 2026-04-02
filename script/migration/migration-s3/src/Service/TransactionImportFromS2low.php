<?php

namespace App\Service;

use App\DatabaseAccess\SelfDB;
use App\DTO\MigrationItem;
use App\Migration\MigrationSourceInterface;

class TransactionImportFromS2low
{
    const LIMIT = 1000;
    public function __construct(
        readonly private MigrationSourceInterface $source,
        readonly private SelfDB $selfDB
    ) {
    }

    public function run($lastProcessedId = null, ?string $minDate = null, ?string $maxDate = null): void
    {
        $id = $lastProcessedId ?? $this->selfDB->getLastId($this->source->getType());
        $count = 0;

        foreach($this->source->getItems($id, $minDate, $maxDate) as $transaction)
        {
            /** @var MigrationItem $transaction */
            $this->selfDB->create($transaction);
            $count++;
            if ($count % 5000 == 0) echo "[IMPORT] {$this->source->getType()} +{$count} (ID: {$transaction->id})" . PHP_EOL;
        }

        if ($count > 0) {
            echo "[IMPORT] {$this->source->getType()->value} done: +{$count} items" . PHP_EOL;
        }
    }
}
