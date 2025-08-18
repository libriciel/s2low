<?php

namespace S2lowLegacy\Class;

use Psr\Log\LoggerInterface;

class AcCertificatesRetrieverWorker implements IWorker
{
    public const QUEUE_NAME = 'certificates-retriever';
    private const COMMAND = "/usr/bin/curl -s https://validca.libriciel.fr/retrieve-validca.sh | /bin/bash -s /etc/s2low/ssl 2>&1";
    private const COMMAND_APACHE = "apachectl graceful";

    public function __construct(private readonly LoggerInterface $logger)
    {
    }
    /**
     * @inheritDoc
     */
    public function getQueueName(): string
    {
        return self::QUEUE_NAME;
    }

    /**
     * @inheritDoc
     */
    public function getData($id): mixed
    {
        return [true];
    }

    /**
     * @inheritDoc
     */
    public function getAllId(): array
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
        if ($logger == "info") {
            $outputApache = null;
            $retvalApache = null;
            exec(self::COMMAND_APACHE, $output, $retval);
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getMutexName($data): string
    {
        return sprintf("%s-%s", self::QUEUE_NAME, $data);
    }

    /**
     * @inheritDoc
     */
    public function isDataValid($data): bool
    {
        return true;
    }

    /**
     * @return void
     */
    public function start(): void
    {
        // TODO: Implement start() method.
    }

    /**
     * @return void
     */
    public function end(): void
    {
        // TODO: Implement end() method.
    }
}
