<?php

namespace S2lowLegacy\Class\actes;

use S2lowLegacy\Class\IActesWorkspace;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;
use S2lowLegacy\Lib\SigTermHandler;
use Monolog\Logger;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @deprecated 5.0.39
 * Le script de ménage doit être réimplémenté en utilisant CloudStorage
 */
class ActesEnvelopeStorage
{
    public const CONTAINER_NAME = 'acte_envelope';

    private $actesEnvelopeSQL;
    private $openStackSwiftWrapper;
    private $logger;


    public function __construct(
        ActesEnvelopeSQL $actesEnvelopeSQL,
        OpenStackSwiftWrapper $openStackSwiftWrapper,
        Logger $logger,
        private readonly IActesWorkspace $workspace
    ) {
        $this->actesEnvelopeSQL = $actesEnvelopeSQL;
        $this->openStackSwiftWrapper = $openStackSwiftWrapper;
        $this->logger = $logger;
    }

    /**
     * @param $min_date
     * @param $max_date
     * @param bool $confirm
     */
    public function grandMenage($min_date, $max_date, $confirm)
    {

        $this->logger->info("Deleting files beetwen $min_date and $max_date");

        $sqlQuery = $this->actesEnvelopeSQL->getOlderTransactionHandle($min_date, $max_date);

        $sigtermHandler = SigTermHandler::getInstance();
        while ($sqlQuery->hasMoreResult()) {
            $actes_envelope = $sqlQuery->fetch();

            $this->logger->debug("Analysing file : {$actes_envelope['file_path']} {$actes_envelope['id']} - {$actes_envelope['submission_date']}");
            $filename = $this->workspace->getFilesUploadRoot() . "/{$actes_envelope['file_path']}";
            if (! file_exists($filename)) {
                $this->logger->debug("File not exists {$actes_envelope['file_path']} [PASS]");
                continue;
            }
            if (
                $this->openStackSwiftWrapper->fileExistsOnCloud(
                    ActesEnvelopeStorage::CONTAINER_NAME,
                    $actes_envelope['file_path']
                )
            ) {
                $this->logger->info("File {$actes_envelope['file_path']} exists on cloud : deleting on file system");
                if ($confirm) {
                    unlink($filename);
                    $this->logger->info("File {$actes_envelope['file_path']} deleted");
                } else {
                    $this->logger->debug("File {$actes_envelope['file_path']} will be deleted if confirm is ok");
                }
            }
            if ($sigtermHandler->isSigtermCalled()) {
                break;
            }
        }
    }
}
