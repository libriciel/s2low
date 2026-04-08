<?php

namespace S2low\Services\Helios\DGFiPConnection;

use phpseclib3\Net\SFTP;
use RuntimeException;
use S2low\Exceptions\ConnectionFailedException;
use S2low\Services\Helios\DGFiPConnection\Protocols\SftpServiceWrapper;

/**
 *  Contient les données de connection vers un serveur SFTP
 */
class SFTPConnection
{
    private string $host;
    private mixed $port;
    private string $login;
    private string $password;
    /**
     * @var SftpServiceWrapper
     */
    private SftpServiceWrapper $sftpServiceWrapper;
    /**
     * @var \phpseclib3\Net\SFTP
     */
    private SFTP $connection;


    /**
     * @param string $host
     * @param string $port
     * @param string $login
     * @param string $password
     * @param SftpServiceWrapper $sftpServiceWrapper
     */
    public function __construct(
        string $host,
        string $port,
        string $login,
        string $password,
        SftpServiceWrapper $sftpServiceWrapper
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->login = $login;
        $this->password = $password;
        $this->sftpServiceWrapper = $sftpServiceWrapper;
    }

    /**
     * @return \phpseclib3\Net\SFTP
     */
    private function getConnection(): SFTP
    {
        if (!isset($this->connection)) {
            throw new RuntimeException('Non connecté (connection)');
        }
        return $this->connection;
    }

    /**
     * @return void
     * @throws ConnectionFailedException
     */
    public function connect(): void
    {
        try {
            $this->connection = $this->sftpServiceWrapper->connect($this->host, $this->port);
            $this->sftpServiceWrapper->login($this->connection, $this->login, $this->password);
        } catch (\Throwable $e) {
            throw new ConnectionFailedException($e->getMessage());
        }
    }

    /**
     * @param string $directory
     * @return bool|array
     * @throws \Exception
     */
    public function nlist(string $directory): bool|array
    {
        return $this->sftpServiceWrapper->nlist($this->getConnection(), $directory);
    }

    /**
     * @param string $tmp_file
     * @param $remoteFile
     * @return bool
     * @throws \S2low\Services\Helios\DGFiPConnection\FTPFileRetrieveException
     * @throws \Exception
     */
    public function get(string $tmp_file, $remoteFile): bool
    {
        $this->sftpServiceWrapper->get($this->getConnection(), $tmp_file, $remoteFile);
        return true;
    }

    /**
     * @param string $remoteFile
     * @param string $file_to_send
     * @return bool
     * @throws \Exception
     */
    public function put(string $remoteFile, string $file_to_send): bool
    {
        $this->sftpServiceWrapper->put($this->getConnection(), $remoteFile, $file_to_send);
        return true;
    }

    /**
     * @return bool
     */
    public function close(): bool
    {
        $this->sftpServiceWrapper->close($this->getConnection());
        return true;
    }

    /**
     * @return string
     */
    public function getURL(): string
    {
        return "ssh2.sftp://$this->login:$this->password@$this->host";
    }

    /**
     * @param string $remote_path
     * @return bool
     */
    public function chdir(string $remote_path): bool
    {
        return $this->sftpServiceWrapper->chdir($this->getConnection(), $remote_path);
    }

    public function pwd()
    {
        return $this->sftpServiceWrapper->pwd($this->getConnection());
    }
}
