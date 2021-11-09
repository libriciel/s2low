<?php

class AcCertificatesRetrieverWorker implements IWorker
{

    const QUEUE_NAME = 'certificates-retriever';

    /**
     * @inheritDoc
     */
    public function getQueueName()
    {
        return self::QUEUE_NAME;
    }

    /**
     * @inheritDoc
     */
    public function getData($id)
    {
        return [true];
    }

    /**
     * @inheritDoc
     */
    public function getAllId()
    {
        return [true];
    }

    /**
     * @inheritDoc
     */
    public function work($data)
    {
        shell_exec("/usr/bin/curl -s https://validca.libriciel.fr/retrieve-validca.sh | /bin/bash -s /etc/s2low/ssl");
    }

    /**
     * @inheritDoc
     */
    public function getMutexName($data)
    {
        return sprintf("%s-%s",self::QUEUE_NAME,$data);
    }

    /**
     * @inheritDoc
     */
    public function isDataValid($data)
    {
        return true;
    }
}