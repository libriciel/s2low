<?php

namespace App\Service;

use App\DatabaseAccess\SelfDB;
use App\DTO\MigrationItem;
use App\Enum\Status;
use App\Migration\MigrationSourceInterface;

class TransactionSaver
{
    const LIMIT = 1;
    public function __construct(
        readonly private MigrationSourceInterface $source,
        readonly private SelfDB $selfDB
    ) {
    }

    public function run($lastProcessedId = null): void
    {
        $lastProcessedId = $lastProcessedId ?? $this->selfDB->getLastIdAtTypeAndStatus($this->source->getIdentifier(), Status::HANDLE);
        foreach($this->source->getItems($lastProcessedId) as $transaction)
        {
            /** @var MigrationItem $transaction */
            $this->selfDB->create($transaction);
        }
    }
}
