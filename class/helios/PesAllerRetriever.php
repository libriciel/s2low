<?php

namespace S2lowLegacy\Class\helios;

use S2lowLegacy\Class\S2lowLogger;
use Exception;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;

class PesAllerRetriever
{
    private $helios_files_upload_root;
    private $openStackSwiftWrapper;
    private $logger;


    public function __construct(
        $helios_files_upload_root,
        OpenStackSwiftWrapper $openStackSwiftWrapper,
        S2lowLogger $logger
    ) {
        $this->helios_files_upload_root = $helios_files_upload_root;
        $this->openStackSwiftWrapper = $openStackSwiftWrapper;
        $this->logger = $logger;
    }

    public function getPath($pes_sha1)
    {
        try {
            $result = $this->openStackSwiftWrapper->retrieveFile(
                PESAllerCloudStorable::CONTAINER_NAME,
                $this->helios_files_upload_root . "/" . $pes_sha1
            );
        } catch (Exception $e) {
            $this->logger->error("Unable to retrieve $pes_sha1 from cloud : " . $e->getMessage(), $e->getTrace());
            return false;
        }

        return $result;
    }

    public function getPathForNonExistingFile($pes_sha1)
    {
        return $this->helios_files_upload_root . "/" . $pes_sha1;
    }
}
