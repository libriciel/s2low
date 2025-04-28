<?php

namespace S2lowLegacy\Class\helios;

use S2lowLegacy\Class\CloudStorage;
use S2lowLegacy\Class\CloudStorageFactory;
use S2lowLegacy\Class\IWorker;
use Exception;
use S2lowLegacy\Lib\UnrecoverableException;

class HeliosMenagePesRetourWorker implements IWorker
{
    public const QUEUE_NAME = 'helios-pes-retour-menage';
    private const NB_DAYS_IN_DISK = 15;

    private $cloudStorageFactory;
    private $cloudStorage;

    public function __construct(
        CloudStorageFactory $cloudStorageFactory,
        private PESRetourCloudStorable $pesRetourCloudStorage
    ) {
        $this->cloudStorageFactory = $cloudStorageFactory;
    }

    /**
     * @return CloudStorage
     * @throws UnrecoverableException
     */
    private function getCloudStorage()
    {
        if (! $this->cloudStorage) {
            $this->cloudStorage = $this->cloudStorageFactory
                ->getInstance($this->pesRetourCloudStorage);
        }
        return $this->cloudStorage;
    }


    public function getQueueName()
    {
        return sprintf("%s-%s", self::QUEUE_NAME, gethostname());
    }

    public function getData($id)
    {
        return $id;
    }

    public function getAllId()
    {
        return [1];
    }

    /**
     * @param $data
     * @return void
     * @throws Exception
     */
    public function work($data)
    {
        $this->getCloudStorage()->deleteFilesOnDisk(self::NB_DAYS_IN_DISK, true);
    }

    public function getMutexName($data)
    {
        return $this->getQueueName();
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
