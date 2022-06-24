<?php

class AcCertificatesRetrieverWorker implements IWorker
{
    public const QUEUE_NAME = 'certificates-retriever';
    private const COMMAND = "/usr/bin/curl -s https://validca.libriciel.fr/retrieve-validca.sh | /bin/bash -s /etc/s2low/ssl 2>&1";

    public function __construct(S2lowLogger $logger)
    {
        $this->logger = $logger;
    }
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
        $output = null;
        $retval = null;
        $this->logger->info(self::COMMAND);
        $execResult = exec(self::COMMAND, $output, $retval);
        $logger = "info";
        if (!$execResult || $retval != 0) {
            $logger = "error";
        }
        foreach ($output as $outputLine) {
            $this->logger->$logger($outputLine);
        }
    }

    /**
     * @inheritDoc
     */
    public function getMutexName($data)
    {
        return sprintf("%s-%s", self::QUEUE_NAME, $data);
    }

    /**
     * @inheritDoc
     */
    public function isDataValid($data)
    {
        return true;
    }
}
