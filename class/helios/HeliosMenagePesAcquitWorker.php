<?php

namespace S2lowLegacy\Class\helios;

use S2lowLegacy\Class\IWorker;
use Exception;

class HeliosMenagePesAcquitWorker implements IWorker
{
    public const QUEUE_NAME = 'helios-pes-acquit-menage';
    private const NB_DAYS_IN_DISK = 15;

    public function __construct(
        private PESAcquitCloudStorage $pesAcquitCloudStorage
    ) {
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
        $this->pesAcquitCloudStorage->deleteFilesOnDisk(self::NB_DAYS_IN_DISK, true);
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
