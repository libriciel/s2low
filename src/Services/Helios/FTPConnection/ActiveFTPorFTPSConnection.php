<?php

namespace S2low\Services\Helios\FTPConnection;

use Exception;
use S2lowLegacy\Lib\FtpServiceWrapper;

/**
 * Contient une connection active vers un serveur FTP ou FTPS
 *
 */
class ActiveFTPorFTPSConnection implements ActiveConnection
{
    /**
     * @var \S2lowLegacy\Lib\FtpServiceWrapper
     */
    private FtpServiceWrapper $ftpServiceWrapper;
    private mixed $ftp;

    public function __construct($ftp, FtpServiceWrapper $ftpServiceWrapper)
    {
        $this->ftp = $ftp;
        $this->ftpServiceWrapper = $ftpServiceWrapper;
    }

    public function chdir(string $remote_path): bool
    {
        return $this->ftpServiceWrapper->chdir($this->ftp, $remote_path);
    }

    public function nlist(string $directory): array|bool
    {
        return $this->ftpServiceWrapper->nlist($this->ftp, $directory);
    }

    public function get(string $tmp_file, string $remoteFile): bool
    {
        return $this->ftpServiceWrapper->get($this->ftp, $tmp_file, $remoteFile);
    }

    public function raw($command): ?array
    {
        return $this->ftpServiceWrapper->raw($this->ftp, $command);
    }

    public function put(string $remoteFile, string $file_to_send): bool
    {
        return $this->ftpServiceWrapper->put($this->ftp, $remoteFile, $file_to_send);
    }

    public function delete($file): bool
    {
        return $this->ftpServiceWrapper->delete($this->ftp, $file);
    }

    public function close(): bool
    {
        return $this->ftpServiceWrapper->close($this->ftp);
    }
}
