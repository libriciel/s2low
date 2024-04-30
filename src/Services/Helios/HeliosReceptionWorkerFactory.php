<?php

namespace S2low\Services\Helios;

use S2lowLegacy\Class\S2lowLogger;
use S2lowLegacy\Class\WorkerScript;

class HeliosReceptionWorkerFactory
{
    /**
     * @var \S2lowLegacy\Class\S2lowLogger
     */
    private S2lowLogger $s2lowLogger;
    /**
     * @var \S2lowLegacy\Class\WorkerScript
     */
    private WorkerScript $workerScript;
    /**
     * @var \S2low\Services\Helios\FTPHeliosReceiverFactory
     */
    private FTPHeliosReceiverFactory $ftpHeliosReceiverFactory;

    public function __construct(
        S2lowLogger $s2lowLogger,
        WorkerScript $workerScript,
        FTPHeliosReceiverFactory $FTPHeliosReceiverFactory
    ) {
        $this->s2lowLogger = $s2lowLogger;
        $this->workerScript = $workerScript;
        $this->ftpHeliosReceiverFactory = $FTPHeliosReceiverFactory;
    }

    public function get(bool $usePasstrans): HeliosReceptionWorker
    {
        return new HeliosReceptionWorker(
            $this->s2lowLogger,
            $this->workerScript,
            $this->ftpHeliosReceiverFactory->get($usePasstrans),
            $usePasstrans
        );
    }
}
