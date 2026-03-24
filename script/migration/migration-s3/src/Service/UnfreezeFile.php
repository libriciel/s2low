<?php

namespace App\Service;

use App\CloudAccess\OldS3;
use App\DatabaseAccess\SelfDB;
use App\DTO\MigrationItem;
use App\Migration\MigrationSourceInterface;

class UnfreezeFile
{
    public function __construct(
        readonly private MigrationSourceInterface $source,
        readonly private SelfDB $selfDB,
        readonly private OldS3 $oldS3
    ) {
    }

    public function run(): void
    {
        foreach ($this->source->getHandledTransactions() as $transaction) {
            /** @var MigrationItem $transaction */
            if ($this->transactionIsFreeze($transaction)) {
                try {
                    $this->oldS3->unfreeze();
                    $this->selfDB->setUnfreeze($transaction);
                } catch (\Exception $e) {
                    $this->selfDB->updateToError($transaction, $e->getMessage());
                }
            }
        }
    }

    private function transactionIsFreeze(MigrationItem $transaction)
    {
        $s3TransactionInfo = $this->oldS3->getInfo($transaction);

        dd($s3TransactionInfo);
        return true;
    }
}
