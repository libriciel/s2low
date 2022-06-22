<?php

class HeliosStorePESAcquitWorker implements IWorker
{
    const QUEUE_NAME = 'helios-store-pes-acquit';

    public function getQueueName()
    {
        return self::QUEUE_NAME;
    }

    private $cloudStorageFactory;
    private $cloudStorage;

    public function __construct(CloudStorageFactory $cloudStorageFactory)
    {
        $this->cloudStorageFactory = $cloudStorageFactory;
    }

    public function getData($id)
    {
        return $id;
    }

    /**
     * @return CloudStorage
     * @throws UnrecoverableException
     */
    private function getCloudStorage()
    {
        if (! $this->cloudStorage) {
            $this->cloudStorage = $this->cloudStorageFactory
                ->getInstanceByClassName(PESAcquitCloudStorage::class);
        }
        return $this->cloudStorage;
    }

    /**
     * @return int[]
     * @throws UnrecoverableException
     */
    public function getAllId()
    {
        return $this->getCloudStorage()->getAllObjectIdToStore();
    }

    /**
     * @param $data
     * @return void
     * @throws CloudStorageException
     * @throws PausingQueueException
     * @throws UnrecoverableException
     */

    public function work($data)
    {
        $this->getCloudStorage()->storeObject($data);
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
