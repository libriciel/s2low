<?php

namespace S2lowLegacy\Class\helios;

use S2lowLegacy\Class\CloudStorage;
use S2lowLegacy\Class\CloudStorageException;
use S2lowLegacy\Class\CloudStorageFactory;
use S2lowLegacy\Class\IWorker;
use S2lowLegacy\Lib\PausingQueueException;
use S2lowLegacy\Lib\UnrecoverableException;

class HeliosStorePESRetourWorker implements IWorker
{
    public const QUEUE_NAME = 'helios-store-pes-retour';

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
                ->getInstanceByClassName(PESRetourCloudStorage::class);
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
     * @throws CloudStorageException | PausingQueueException | UnrecoverableException
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
