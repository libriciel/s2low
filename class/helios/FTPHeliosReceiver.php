<?php

namespace S2lowLegacy\Class\helios;

use S2lowLegacy\Class\S2lowLogger;
use Exception;
use Iterator;

class FTPHeliosReceiver implements Iterator
{
     /**
     * @var FTPService
     */
    private $FTPService;
    private $remotePath;
    private $localPath;
    private $filesToProcess = [];
    private $index = 0;
    private $s2lowLogger;

    public function __construct(
        S2lowLogger $s2lowLogger,
        FTPService $FTPService,
        $helios_ftp_response_server_path,
        $helios_ftp_response_tmp_local_path
    ) {
        $this->s2lowLogger = $s2lowLogger;
        $this->FTPService = $FTPService;
        $this->remotePath = $helios_ftp_response_server_path;
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

    public function retrieveNames()
    {
        $this->FTPService->connect();

        $this->s2lowLogger->info("Remote_path : $this->remotePath");

        $all_file = $this->FTPService->getFileNames($this->remotePath);
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
        $ftp_get_result = $this->FTPService->retrieveFile($file, $this->localPath);
        $this->s2lowLogger->info($i . " : " . $file . " récupéré : " . ($ftp_get_result ? "SUCCES" : "ECHEC")) ;
    }

    public function finTraitement()
    {
        $this->FTPService->disconnect();
    }
}
