<?php

declare(strict_types=1);

namespace S2low\Services\Helios;

use Exception;
use Iterator;
use S2low\Services\Helios\DGFiPConnection\DGFiPConnection;
use S2lowLegacy\Class\S2lowLogger;

/**
 *
 */
class FTPHeliosReceiver implements Iterator
{
    private string $localPath;
    private array $filesToProcess = [];
    private int $index = 0;
    private S2lowLogger $s2lowLogger;
    /**
     * @var \S2low\Services\Helios\DGFiPConnection\DGFiPConnection|null
     */
    private ?DGFiPConnection $heliosConnection;
    private string $tmp_path;
    private string $helios_responses_error_path;

    /**
     * @param S2lowLogger $s2lowLogger
     * @param DGFiPConnection $heliosConnection
     * @param string $helios_ftp_response_tmp_local_path
     * @param string $helios_responses_error_path
     */
    public function __construct(
        S2lowLogger $s2lowLogger,
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
     * @throws \Exception
     */
    public function current(): string
    {
        $this->recupOneFile($this->filesToProcess[$this->index], $this->key());
        return $this->filesToProcess[$this->index];
    }

    /**
     * Return the key of the current element
     * @link https://php.net/manual/en/iterator.key.php
     * @return int TKey on success, or null on failure.
     */
    public function key(): int
    {
        return $this->index;
    }

    /**
     * Move forward to next element
     * @link https://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next(): void
    {
        $this->index++;
    }

    /**
     * Checks if current position is valid
     * @link https://php.net/manual/en/iterator.valid.php
     * @return bool The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid(): bool
    {
        $valid = isset($this->filesToProcess[$this->key()]);
        if (!$valid) {
            $this->finTraitement();
        }
        return $valid;
    }

    /**
     * Rewind the Iterator to the first element
     * @link https://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind(): void
    {
        $this->index = 0;
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
    public function retrieveNames(): void
    {
        $this->heliosConnection->connect();                     //TODO : MOCHE !!
        $all_file = $this->heliosConnection->getFileNames();
        $this->filesToProcess = [];

        foreach ($all_file as $file) {
            if (!$this->isPesAller(basename($file))) {
                $this->filesToProcess[] = $file;
            }
        }
    }

    /**
     * @param $file
     * @param $i
     * @throws Exception
     */
    private function recupOneFile($file, $i): void
    {
        try {
            $this->heliosConnection->retrieveFile(
                $file,
                $this->localPath,
                $this->helios_responses_error_path,
                $this->tmp_path
            );
        } catch (Exception $exception) {
            $this->s2lowLogger->info("$i : $file récupéré : ECHEC " . $exception->getMessage());
            return;
        }
        $this->s2lowLogger->info("$i : $file récupéré : SUCCES") ;
    }

    /**
     * @return void
     */
    public function finTraitement(): void
    {
        $this->heliosConnection->disconnect();
    }
}
