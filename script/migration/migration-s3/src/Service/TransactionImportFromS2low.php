<?php

namespace App\Service;

use App\DatabaseAccess\SelfDB;
use App\DTO\MigrationItem;
use App\Enum\Status;
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
        $id = $lastProcessedId ?? $this->selfDB->getLastId($this->source->getIdentifier());
        $count = 0;
        echo "   -> [" . $this->source->getIdentifier() . "] Scanning from ID > $id (MinDate: ".($minDate ?? 'none').", MaxDate: ".($maxDate ?? 'none').")" . PHP_EOL;

        foreach($this->source->getItems($id, $minDate, $maxDate) as $transaction)
        {
            /** @var MigrationItem $transaction */
            $this->selfDB->create($transaction);
            $count++;
            if ($count % 1000 == 0) echo "      [" . $this->source->getIdentifier() . "] Imported " . ($count) . " items (last ID: ".$transaction->id.")" . PHP_EOL;
        }
        echo "   -> [" . $this->source->getIdentifier() . "] Done. $count new items imported." . PHP_EOL;
    }
}
