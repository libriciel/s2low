<?php

class HeliosEnvoiWorker implements IWorker
{
    public const QUEUE_NAME = 'helios-envoi';


    private $heliosEnvoiControler;
    private $heliosTransactionsSQL;

    public function __construct(
        HeliosEnvoiControler $heliosEnvoiControler,
        HeliosTransactionsSQL $heliosTransactionsSQL
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
        return $this->heliosTransactionsSQL->getIdsByStatus(HeliosTransactionsSQL::ATTENTE);
    }

    /**
     * @param $data
     * @return void
     * @throws Exception
     */
    public function work($data)
    {
        $this->heliosEnvoiControler->sendOneTransaction($data);
    }

    public function getMutexName($data)
    {
        return sprintf("helios-transaction-%s", $data);
    }

    public function isDataValid($data)
    {
        $status_id = $this->heliosTransactionsSQL->getLatestStatusId($data);
        return $status_id == HeliosStatusSQL::ATTENTE;
    }
}
