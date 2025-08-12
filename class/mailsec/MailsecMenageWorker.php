<?php

namespace S2lowLegacy\Class\mailsec;

use S2lowLegacy\Class\IWorker;
use Exception;

class MailsecMenageWorker implements IWorker
{
    public const QUEUE_NAME = 'mailsec-menage';
    private const NB_DAYS_IN_DISK = 15;

    public function __construct(
        private MailIncludedFilesCloudStorage $mailIncludedFilesCloudStorage
    ) {
    }


    public function getQueueName(): string
    {
        return sprintf("%s-%s", self::QUEUE_NAME, gethostname());
    }

    public function getData($id): mixed
    {
        return $id;
    }

    public function getAllId(): array
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

    public function isDataValid($data): bool
    {
        return true;
    }
}
