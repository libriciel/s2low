<?php

namespace S2lowLegacy\Class\actes;

use S2low\Application\UseCase\AnalyserActesAntivirus;
use S2low\Legacy\LegacyServiceContainer;
use S2lowLegacy\Class\Antivirus;
use S2lowLegacy\Class\IWorker;
use S2lowLegacy\Class\S2lowLogger;
use S2lowLegacy\Class\WorkerScript;
use Exception;

class ActesAntivirusWorker implements IWorker
{
    public const QUEUE_NAME = 'actes-antivirus';

    private $actesTransactionSQL;
    private $logger;

    private $workerScript;


    public function __construct(
        ActesTransactionsSQL $actesTransactionSQL,
        S2lowLogger $s2lowLogger,
        WorkerScript $workerScript,
    ) {
        $this->actesTransactionSQL = $actesTransactionSQL;
        $this->logger = $s2lowLogger;
        $this->workerScript = $workerScript;
    }

    public function getQueueName()
    {
        return self::QUEUE_NAME;
    }

    public function getAllId()
    {
        return $this->actesTransactionSQL->getTransactionForAntiVirus();
    }

    public function getData($id)
    {
        return $id;
    }

    public function getMutexName($data)
    {
        return sprintf("actes-transaction-%s", $data);
    }

    public function isDataValid($data)
    {
        $transaction_id = $data;
        $transaction_info = $this->actesTransactionSQL->getInfo($transaction_id);
        if ($transaction_info['antivirus_check']) {
            $this->logger->notice("La transaction $transaction_id a déjà été analysé par l'antivirus");
            return false;
        }
        return true;
    }

    /**
     * @param $data
     * @return bool
     * @throws Exception
     */
    public function work($data)
    {
        $transaction_id = $data;
        $transaction_info = $this->actesTransactionSQL->getInfo($transaction_id);

        /*
         * Debut du nouveau code
         */
        $legacyServiceContainer = LegacyServiceContainer::getServiceContainer();
        $analyserActesAntivirusService = $legacyServiceContainer->get('analyser_actes_antivirus');

        $analyserActesAntivirusService->execute($transaction_id);
        /*
         * Fin du nouveau code
         */


        $this->workerScript->putJobByClassName(
            ActesAnalyseFichierAEnvoyerWorker::class,
            $transaction_info["envelope_id"]
        );
        return true;
    }

    /**
     * @return void
     */
    public function start()
    {
        // TODO: Implement start() method.
    }

    /**
     * @return void
     */
    public function end()
    {
        // TODO: Implement end() method.
    }
}
