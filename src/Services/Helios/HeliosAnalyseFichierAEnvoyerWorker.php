<?php

namespace S2low\Services\Helios;

use Exception;
use S2lowLegacy\Class\IWorker;
use S2lowLegacy\Class\WorkerScript;
use S2lowLegacy\Model\HeliosTransactionsSQL;

class HeliosAnalyseFichierAEnvoyerWorker implements IWorker
{
    public const QUEUE_NAME = 'helios-analyse-fichier-a-envoyer';


    private $heliosEnvoiControler;
    private $heliosTransactionsSQL;
    private $workerScript;

    public function __construct(
        HeliosEnvoiControler $heliosEnvoiControler,
        HeliosTransactionsSQL $heliosTransactionsSQL,
        WorkerScript $workerScript
    ) {
        $this->heliosEnvoiControler = $heliosEnvoiControler;
        $this->heliosTransactionsSQL = $heliosTransactionsSQL;
    }

    public function getQueueName()
    {
        return self::QUEUE_NAME;
    }

    public function getData($id)
    {
        return $id;
    }

    /**
     * @return array|false|int[]
     * @throws Exception
     */
    public function getAllId()
    {
        return $this->heliosTransactionsSQL->getIdsByStatus(HeliosTransactionsSQL::POSTE);
    }

    /**
     * @param $data
     * @return void
     * @throws Exception
     */
    public function work($data)
    {
        $this->heliosEnvoiControler->validateOneTransaction($data);
    }

    public function getMutexName($data)
    {
        return sprintf("helios-transaction-%s", $data);
    }

    public function isDataValid($data)
    {
        $status_id = $this->heliosTransactionsSQL->getLatestStatusId($data);

        return $status_id == HeliosTransactionsSQL::POSTE;
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
