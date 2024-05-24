<?php

namespace S2lowLegacy\Class\helios;

use Exception;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;
use S2lowLegacy\Lib\SigTermHandler;
use S2lowLegacy\Lib\UnrecoverableException;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use Monolog\Logger;
use Symfony\Component\Filesystem\Filesystem;

class PesAllerStorage
{
    public const CONTAINER_NAME = "pes_aller";

    private $helios_files_upload_root;
    private $heliosTransactionsSQL;
    private $openStackSwiftWrapper;
    private $logger;
    private $repertoirePesAllerSansTransaction; // "/data/tdt-workspace/mail/helios_orphelins/"


    public function __construct(
        $helios_files_upload_root,
        HeliosTransactionsSQL $heliosTransactionsSQL,
        OpenStackSwiftWrapper $openStackSwiftWrapper,
        Logger $logger,
        $repertoirePesAllerSansTransaction
    ) {
        $this->helios_files_upload_root = $helios_files_upload_root;
        $this->heliosTransactionsSQL = $heliosTransactionsSQL;
        $this->openStackSwiftWrapper = $openStackSwiftWrapper;
        $this->logger = $logger;
        $this->repertoirePesAllerSansTransaction = $repertoirePesAllerSansTransaction;
    }

    /**
     * @param int $no_access_during_nb_days
     * @throws Exception
     */
    public function menageLocal($no_access_during_nb_days = 9999)
    {

        $sigtermHandler = SigTermHandler::getInstance();
        $dh = opendir($this->helios_files_upload_root);
        if (! $dh) {
            throw new UnrecoverableException("Impossible d'ouvrir " . $this->helios_files_upload_root);
        }

        while (($file = readdir($dh)) !== false) {
            if ($sigtermHandler->isSigtermCalled()) {
                break;
            }
            if (in_array($file, array('.','..'))) {
                continue;
            }
            if ($this->isRecentlyCreated($file, $no_access_during_nb_days)) {
                $this->logger->debug("File $file too young to die : not deleted");
                continue;
            }
            if (!$this->fileExistsOnCloud($file)) {
                $this->logger->info("File $file not existing on cloud : not deleted");
                $this->handleOlderFileNotInCloud($file);
                continue;
            }
            $this->logger->info("Deleting file : $file");
            unlink($this->helios_files_upload_root . "/" . $file);
        }
        closedir($dh);
    }

    private function isRecentlyCreated($filename, $no_access_during_nb_days = 9999)
    {
        $last_access_time = filemtime($this->helios_files_upload_root . "/" . $filename);
        $nb_seconds_without_access = time() - $last_access_time;
        $no_access_during_nb_seconds = $no_access_during_nb_days * 86400;
        $this->logger->debug("Nombre de jour depuis la derniere modif : " . round($nb_seconds_without_access / 60 / 60 / 24));
        return ($nb_seconds_without_access < $no_access_during_nb_seconds);
    }

    /**
     * @param $file
     */
    private function moveToOrphelinsDirectory($file): void
    {
        if (is_null($this->repertoirePesAllerSansTransaction)) {
            $this->logger->info(
                "File $file : destination directory $this->repertoirePesAllerSansTransaction not found"
            );
            return;
        }
        if (
            !rename(
                $this->helios_files_upload_root . "/" . $file,
                $this->repertoirePesAllerSansTransaction . "/" . $file
            )
        ) {
            $this->logger->info("File $file : rename KO");
            return;
        }
    }

    /**
     * @param $file
     */
    private function handleOlderFileNotInCloud($file): void
    {
        $id = $this->heliosTransactionsSQL->getIdBySHA1($file);
        if (!$id) {
            $this->logger->info("$file No transaction id found");
            $this->moveToOrphelinsDirectory($file);
            return;
        }
        if (!$this->heliosTransactionsSQL->isTransactionAvailable($id)) {
            $this->logger->info("$file [transaction $id] passé à not_available = false");
            $this->heliosTransactionsSQL->setTransactionAvailable($id, true);
        }
        if ($this->heliosTransactionsSQL->isTransactionInCloud($id)) {
            $this->logger->info("$file [transaction $id] passé à is_in_cloud = false");
            $this->heliosTransactionsSQL->setTransactionInCloudRemove($id);
        }
    }

    /**
     * @param $file
     * @return bool|\Psr\Http\Message\ResponseInterface
     */
    private function fileExistsOnCloud($file)
    {
        return $this->openStackSwiftWrapper->fileExistsOnCloud(
            self::CONTAINER_NAME,
            $file
        );
    }
}
