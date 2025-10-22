<?php

namespace S2lowLegacy\Class\actes;

use Exception;
use Psr\Log\LoggerInterface;
use S2low\Services\CloudFileStorageInterface;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;
use S2lowLegacy\Lib\SigTermHandler;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

class ActesMenage
{
    private $actes_files_upload_root;
    private $actesEnvelopeSQL;
    private $openStackSwiftWrapper;
    private LoggerInterface $logger;


    public function __construct(
        $actes_files_upload_root,
        ActesEnvelopeSQL $actesEnvelopeSQL,
        LoggerInterface $logger,
        #[Autowire(service: 'app.store.file.acte_enveloppe')]
        private readonly CloudFileStorageInterface $cloudFileStorage,
    ) {
        $this->actes_files_upload_root = $actes_files_upload_root;
        $this->actesEnvelopeSQL = $actesEnvelopeSQL;
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
            $filename = $this->actes_files_upload_root . "/{$actes_envelope['file_path']}";
            if (! file_exists($filename)) {
                $this->logger->debug("File not exists {$actes_envelope['file_path']} [PASS]");
                continue;
            }
            if (
                $this->cloudFileStorage->fileExistOnCloud($actes_envelope['id'])
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
