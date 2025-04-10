<?php

namespace S2lowLegacy\Class\helios;

use S2lowLegacy\Class\S2lowLogger;
use Exception;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;

class PesAllerRetriever
{
    private $openStackSwiftWrapper;
    private $logger;


    public function __construct(
        OpenStackSwiftWrapper $openStackSwiftWrapper,
        S2lowLogger $logger,
        private readonly IWorkspace $workspace
    ) {
        $this->openStackSwiftWrapper = $openStackSwiftWrapper;
        $this->logger = $logger;
    }

    public function getPath($pes_sha1)
    {
        try {
            $result = $this->openStackSwiftWrapper->retrieveFile(
                PESAllerCloudStorage::CONTAINER_NAME,
                $this->workspace->getHeliosFilesUploadRoot() . '/' . $pes_sha1
            );
        } catch (Exception $e) {
            $this->logger->error("Unable to retrieve $pes_sha1 from cloud : " . $e->getMessage(), $e->getTrace());
            return false;
        }

        return $result;
    }

    public function getPathForNonExistingFile($pes_sha1)
    {
        return $this->workspace->getHeliosFilesUploadRoot() . "/" . $pes_sha1;
    }
}
