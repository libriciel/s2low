<?php

namespace S2low\Services\Helios;

use S2lowLegacy\Model\HeliosTransactionsSQL;

class HeliosEnvoiWorkerFactory
{
    public function __construct(
        private readonly HeliosEnvoiControler $heliosEnvoiControler,
        private readonly HeliosTransactionsSQL $heliosTransactionsSQL,
    ) {
    }

    public function get(bool $usePasstrans): HeliosEnvoiWorker
    {
        return new HeliosEnvoiWorker(
            $this->heliosEnvoiControler,
            $this->heliosTransactionsSQL,
            $usePasstrans
        );
    }
}
