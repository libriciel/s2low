<?php

class ActesMenageEnveloppeWorker implements IWorker
{
    public const QUEUE_NAME = 'actes-enveloppe-menage';
    private const NB_DAYS_IN_DISK = 15;

    private $cloudStorageFactory;
    private $cloudStorage;
    private $nb_days_in_disk;

    public function __construct(CloudStorageFactory $cloudStorageFactory)
    {
        $this->cloudStorageFactory = $cloudStorageFactory;
        $this->setNbDayInDisk(self::NB_DAYS_IN_DISK);
    }

    /**
     * @return CloudStorage
     * @throws UnrecoverableException
     */
    private function getCloudStorage()
    {
        if (! $this->cloudStorage) {
            $this->cloudStorage = $this->cloudStorageFactory
                ->getInstanceByClassName(ActesCloudStorage::class);
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

    public function setNbDayInDisk(int $nb_days_in_disk)
    {
        $this->nb_days_in_disk = $nb_days_in_disk;
    }

    /**
     * @param $data
     * @return void
     * @throws Exception
     */
    public function work($data)
    {
        $this->getCloudStorage()->deleteFilesOnDisk($this->nb_days_in_disk, true);
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
