<?php

namespace S2lowLegacy\Class\actes;

use Exception;
use Psr\Log\LoggerInterface;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;

class ActesRetriever
{
    private $actes_files_upload_root;
    private $openStackSwiftWrapper;
    private $logger;

    public function __construct(
        $actes_files_upload_root,
        OpenStackSwiftWrapper $openStackSwiftWrapper,
        LoggerInterface $logger
    ) {
        $this->actes_files_upload_root = $actes_files_upload_root;
        $this->openStackSwiftWrapper = $openStackSwiftWrapper;
        $this->logger = $logger;
    }

    public function getPath($acte_path)
    {
        try {
            $result = $this->openStackSwiftWrapper->retrieveFile(
                ActesCloudStorable::CONTAINER_NAME,
                $this->actes_files_upload_root . "/" . $acte_path,
                $acte_path
            );
        } catch (Exception $e) {
            $this->logger->error("Unable to retrieve $acte_path from cloud : " . $e->getMessage(), $e->getTrace());
            return false;
        }
        return $result;
    }
}
