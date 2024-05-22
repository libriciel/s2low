<?php

namespace S2low\DTO;

class SAEStateTransitionRequest
{
    public int $transaction_id;

    public int $status_id;

    public function __construct(int $transaction_id, int $status_id)
    {
        $this->transaction_id = $transaction_id;
        $this->status_id = $status_id;
    }
}
