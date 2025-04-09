<?php

namespace S2lowLegacy\Class\actes;

use S2lowLegacy\Class\ActesWorkspace;
use S2lowLegacy\Class\S2lowLogger;
use Exception;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;

class ActesRetriever
{
    private $openStackSwiftWrapper;
    private $logger;

    public function __construct(
        OpenStackSwiftWrapper $openStackSwiftWrapper,
        S2lowLogger $logger,
        private readonly ActesWorkspace $workspace
    ) {
        $this->openStackSwiftWrapper = $openStackSwiftWrapper;
        $this->logger = $logger;
    }

    public function getPath($acte_path)
    {
        try {
            $result = $this->openStackSwiftWrapper->retrieveFile(
                ActesEnvelopeStorage::CONTAINER_NAME,
                $this->workspace->getActesFilesUploadRoot() . '/' . $acte_path,
                $acte_path
            );
        } catch (Exception $e) {
            $this->logger->error("Unable to retrieve $acte_path from cloud : " . $e->getMessage(), $e->getTrace());
            return false;
        }
        return $result;
    }
}
