<?php

namespace S2low\Domain\Model\Transaction;

class TestClasse
{
    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }
}