<?php

declare(strict_types=1);

namespace S2low\Services\Helios;

use Exception;
use Psr\Log\LoggerInterface;
use S2low\Exceptions\ConnectionFailedException;
use S2low\Services\Helios\DGFiPConnection\DGFiPConnection;

/**
 *
 */
class FTPHeliosReceiver
{
    private string $localPath;

    private LoggerInterface $s2lowLogger;
    /**
     * @var \S2low\Services\Helios\DGFiPConnection\DGFiPConnection|null
     */
    private ?DGFiPConnection $heliosConnection;
    private string $tmp_path;
    private string $helios_responses_error_path;

    /**
     * @param LoggerInterface $s2lowLogger
     * @param DGFiPConnection $heliosConnection
     * @param string $helios_ftp_response_tmp_local_path
     * @param string $helios_responses_error_path
     */
    public function __construct(
        LoggerInterface $s2lowLogger,
        DGFiPConnection $heliosConnection,
        string $helios_ftp_response_tmp_local_path,
        string $helios_responses_error_path
    ) {
        $this->s2lowLogger = $s2lowLogger;
        $this->heliosConnection = $heliosConnection;
        $this->localPath = $helios_ftp_response_tmp_local_path;
        $this->helios_responses_error_path = $helios_responses_error_path;
        $this->tmp_path = sys_get_temp_dir();
    }

    /**
     * @param string $filename
     * @return bool
     */
    private function isPesAller(string $filename): bool
    {
        $isPesAller = str_starts_with(basename($filename), 'PESALR2_');
        if ($isPesAller) {
            $this->s2lowLogger->info("$filename : PES ALLER ignoré");
        }
        return $isPesAller;
    }

    /**
     * @throws Exception
     */
    public function retrieveNames(int $waitingTime = 5): array
    {
        $count = null;
        $growing = true;

        while ($growing) {
            $previousCount = $count;
            $all_file = $this->heliosConnection->getFileNames();
            $count = count($all_file);
            if (!is_null($count) && !is_null($previousCount) && $count <= $previousCount) {
                $growing = false;
            }
            if (!is_null($previousCount) && $growing) {
                $this->s2lowLogger->info(
                    "Des PES sont en cours d'écriture ($previousCount à $count en $waitingTime s)"
                );
            }
            if ($growing) {
                sleep($waitingTime);
            }
        }
        $filesToProcess = [];

        foreach ($all_file as $file) {
            if (!$this->isPesAller(basename($file))) {
                $filesToProcess[] = $file;
            }
        }
        return $filesToProcess;
    }

    /**
     * @param $file
     * @throws \S2low\Services\Helios\DGFiPConnection\FTPFileRetrieveException
     * @throws Exception
     */
    public function recupOneFile($file): void
    {
        try {
            $this->heliosConnection->retrieveFile(
                $file,
                $this->localPath,
                $this->helios_responses_error_path,
                $this->tmp_path
            );
        } catch (Exception $exception) {
            $this->s2lowLogger->info("$file récupéré : ECHEC " . $exception->getMessage());
            throw $exception;
        }
        $this->s2lowLogger->info("$file récupéré : SUCCES") ;
    }

    /**
     * @return void
     */
    public function finTraitement(): void
    {
        $this->heliosConnection->disconnect();
    }

    /**
     * @throws ConnectionFailedException
     */
    public function debutTraitement(): void
    {
        $this->heliosConnection->connect();
        $this->heliosConnection->moveToReponsePath();
    }
}
