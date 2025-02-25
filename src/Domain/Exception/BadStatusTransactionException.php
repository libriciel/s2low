<?php

namespace S2low\Domain\Exception;

use S2low\Domain\Model\ValueObject\StatusTransaction;

class BadStatusTransactionException extends \RuntimeException
{
    public function __construct(int $transactionId, StatusTransaction $goodStatus, StatusTransaction $badStatus)
    {
        parent::__construct("Le statut de la transaction '$transactionId' est incorrect. La transaction devrait etre au statut '$goodStatus->name' mais est au statut '$badStatus->name'.");
    }
}