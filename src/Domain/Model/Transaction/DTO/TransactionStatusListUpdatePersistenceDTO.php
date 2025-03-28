<?php

namespace S2low\Domain\Model\Transaction\DTO;

use S2low\Domain\Model\Transaction\TransactionStatusListUpdate;

class TransactionStatusListUpdatePersistenceDTO
{
    public array $transactionStatusList;

    /**
     * @param array $transactionStatusList
     */
    public function __construct(TransactionStatusHistoryPersistenceDTO ...$transactionStatusList)
    {
        $this->transactionStatusList = $transactionStatusList;
    }

    public function toModel(): TransactionStatusListUpdate
    {
        $transactionStatusList = [];
        foreach ($this->transactionStatusList as $transactionStatus) {
            $transactionStatusList[] = $transactionStatus->toModel();
        }

        return new TransactionStatusListUpdate(
            ...$transactionStatusList
        );
    }

    public function getMostRecent()
    {
        $copyList = $this->transactionStatusList;
        usort($copyList, function ($a, $b) {
            return $b->date <=> $a->date;
        });

        return $copyList[0];
    }
}
