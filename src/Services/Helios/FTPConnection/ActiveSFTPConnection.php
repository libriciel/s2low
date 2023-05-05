<?php

namespace S2low\Services\Helios\FTPConnection;

use S2lowLegacy\Lib\SftpServiceWrapper;

class ActiveSFTPConnection implements ActiveConnection
{
    public function __construct(
        $sftp,
        $connection,
        SftpServiceWrapper $sftpServiceWrapper
    ) {
        $this->sftp = $sftp;
        $this->connection = $connection;
        $this->sftpServiceWrapper = $sftpServiceWrapper;
    }

    public function nlist(string $directory): bool|array
    {
        return $this->sftpServiceWrapper->nlist($this->sftp, $directory);
    }

    public function get(string $tmp_file, $remoteFile): bool
    {
        $this->sftpServiceWrapper->get($this->sftp, $tmp_file, $remoteFile);
        return true;
    }

    public function put(string $remoteFile, string $file_to_send): bool
    {
        $this->sftpServiceWrapper->put($this->sftp, $remoteFile, $file_to_send);
        return true;
    }

    public function close(): bool
    {
        $this->sftpServiceWrapper->close($this->connection);
        return true;
    }

    public function chdir(string $remote_path): bool
    {
        throw new \Exception("Fonction non implémentée en SFTP");
    }

    public function raw($command): ?array
    {
        throw new \Exception("Fonction non implémentée en SFTP");
    }

    public function delete($file): bool
    {
        throw new \Exception("Fonction non implémentée en SFTP");
    }
}
