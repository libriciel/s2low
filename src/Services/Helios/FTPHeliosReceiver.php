<?php

namespace S2low\Services\Helios;

use Exception;
use Iterator;
use S2lowLegacy\Class\S2lowLogger;

class FTPHeliosReceiver implements Iterator
{
    private $localPath;
    private $filesToProcess = [];
    private $index = 0;
    private $s2lowLogger;
    /**
     * @var \S2low\Services\Helios\HeliosConnection|null
     */
    private HeliosConnection $heliosConnection;
    /**
     * @var \S2low\Services\Helios\HeliosConnectionsConfigurationManager
     */

    public function __construct(
        S2lowLogger $s2lowLogger,
        HeliosConnection $heliosConnection,
        $helios_ftp_response_tmp_local_path
    ) {
        $this->s2lowLogger = $s2lowLogger;
        $this->heliosConnection = $heliosConnection;
        $this->localPath = $helios_ftp_response_tmp_local_path;
    }

    /**
     * @throws \Exception
     */
    public function current(): string
    {
        $this->recupOneFile($this->filesToProcess[$this->index], $this->key());
        return $this->filesToProcess[$this->index];
    }

    public function key(): int
    {
        return $this->index;
    }

    public function next(): void
    {
        $this->index++;
    }

    public function valid(): bool
    {
        $valid = isset($this->filesToProcess[$this->key()]);
        if (!$valid) {
            $this->finTraitement();
        }
        return $valid;
    }

    public function rewind(): void
    {
        $this->index = 0;
    }

    private function isPesAller($filename): bool
    {
        $isPesAller = preg_match("#^PESALR2_#", basename($filename));
        if ($isPesAller) {
            $this->s2lowLogger->info("$filename : PES ALLER ignoré");
        }
        return $isPesAller;
    }

    /**
     * @throws \Exception
     */
    public function retrieveNames()
    {

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
        $ftp_get_result = $this->heliosConnection->retrieveFile($file, $this->localPath);
        $this->s2lowLogger->info($i . " : " . $file . " récupéré : " . ($ftp_get_result ? "SUCCES" : "ECHEC")) ;
    }

    public function finTraitement()
    {
        $this->heliosConnection->disconnect();
    }
}
