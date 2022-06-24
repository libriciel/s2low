<?php

class HeliosStorePESAllerWorker implements IWorker
{
    public const QUEUE_NAME = 'helios-store-pes-aller';

    public function getQueueName()
    {
        return self::QUEUE_NAME;
    }

    private $pesAllerStorage;

    public function __construct(PesAllerStorage $pesAllerStorage)
    {
        $this->pesAllerStorage = $pesAllerStorage;
    }

    public function getData($id)
    {
        return $id;
    }

    public function getAllId()
    {
        return $this->pesAllerStorage->getAllTransactionIdToStore();
    }

    /**
     * @param $data
     * @return void
     * @throws Exception
     */
    public function work($data)
    {
        $this->pesAllerStorage->storeNextFileById($data);
    }

    public function getMutexName($data)
    {
        return sprintf("%s-%s", self::QUEUE_NAME, $data);
    }

    public function isDataValid($data)
    {
        return true;
    }
}
