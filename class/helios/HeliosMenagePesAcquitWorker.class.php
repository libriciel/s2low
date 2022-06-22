<?php

class HeliosMenagePesAcquitWorker implements IWorker
{
    public const QUEUE_NAME = 'helios-pes-acquit-menage';
    private const NB_DAYS_IN_DISK = 15;

    private $cloudStorageFactory;
    private $cloudStorage;

    public function __construct(CloudStorageFactory $cloudStorageFactory)
    {
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
                ->getInstanceByClassName(PESAcquitCloudStorage::class);
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
}
