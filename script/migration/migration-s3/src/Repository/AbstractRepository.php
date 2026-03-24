<?php

namespace App\Repository;

use App\DatabaseAccess\S2lowDB;

abstract class AbstractRepository
{
    public function __construct(
        protected S2lowDB $connexion
    ) {
    }

    abstract public function getHandledTransactions($lastProcessedId);
}
