<?php

namespace App\Service;

use App\DatabaseAccess\SelfDB;
use App\DTO\MigrationItem;
use App\Repository\AbstractRepository;

class PrepareTransactionToDownload
{

    public function __construct(
        private readonly AbstractRepository $repository,
        private readonly SelfDB $selfDB,
    ) {
    }

    public function run(): void
    {
        $lastProcessedId = $this->selfDB->getLastIdAtTypeAndStatus();
        foreach($this->repository->getHandledTransactions($lastProcessedId) as $transaction)
        {
            /** @var MigrationItem $transaction */
            $this->selfDB->create($transaction);
        }
    }
}
