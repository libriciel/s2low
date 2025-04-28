<?php

namespace S2lowLegacy\Class\mailsec;

use S2lowLegacy\Class\CloudStorageException;
use S2lowLegacy\Class\IWorker;
use S2lowLegacy\Lib\PausingQueueException;
use S2lowLegacy\Lib\UnrecoverableException;

class MailsecStoreFilesWorker implements IWorker
{
    public const QUEUE_NAME = 'mailsec-included-file';

    public function getQueueName()
    {
        return self::QUEUE_NAME;
    }

    public function __construct(
        private MailIncludedFilesCloudStorage $mailIncludedFilesCloudStorage
    ) {
    }

    public function getData($id)
    {
        return $id;
    }

    /**
     * @return int[]
     * @throws UnrecoverableException
     */
    public function getAllId()
    {
        return $this->mailIncludedFilesCloudStorage->getAllObjectIdToStore();
    }

    /**
     * @param $data
     * @return void
     * @throws CloudStorageException | PausingQueueException | UnrecoverableException
     */

    public function work($data)
    {
        $this->mailIncludedFilesCloudStorage->storeObject($data);
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
