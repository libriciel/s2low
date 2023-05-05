<?php

namespace S2low\Services\Helios;

use Exception;
use S2low\Services\Helios\FTPConnection\ActiveConnectionFactory;
use S2low\Services\Helios\FTPConnection\FullConfiguration;
use S2lowLegacy\Class\S2lowLogger;

class HeliosConnectionBuilder
{
    /**
     * @var false|resource
     */
    private $logger;
    /**
     * @var \S2low\Services\Helios\FTPConnection\ActiveConnectionFactory
     */
    private ActiveConnectionFactory $activeConnectionFactory;

    public function __construct(
        S2lowLogger $s2lowLogger,
        ActiveConnectionFactory $activeConnectionFactory,
    ) {
        $this->logger = $s2lowLogger;
        $this->activeConnectionFactory = $activeConnectionFactory;
    }

    /**
     * @return void
     * @throws Exception
     */
    public function connect(FullConfiguration $fullConfiguration): HeliosConnection
    {

        $this->logger->info("Connection à {$fullConfiguration->getDescription()}");

        return new HeliosConnection(
            $this->activeConnectionFactory->get($fullConfiguration->getConnectionConfiguration()),
            $this->logger,
            $fullConfiguration->getServerPathsConfiguration(),
            $fullConfiguration->getServerProtocolConfiguration()
        );
    }
}
