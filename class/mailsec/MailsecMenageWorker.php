<?php

namespace S2lowLegacy\Class\mailsec;

use S2lowLegacy\Class\IWorker;
use Exception;

class MailsecMenageWorker implements IWorker
{
    public const QUEUE_NAME = 'mailsec-menage';
    private const NB_DAYS_IN_DISK = 15;

    public function __construct(
        private MailIncludedFilesCloudStorable $mailIncludedFilesCloudStorage
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
        $this->mailIncludedFilesCloudStorage->deleteFilesOnDisk(self::NB_DAYS_IN_DISK, true);
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
