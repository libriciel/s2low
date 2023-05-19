<?php

namespace S2low\Services\Helios\DGFiPConnection;

use RuntimeException;
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
     * @var resource
     */
    private $connection;
    /**
     * @var resource
     */
    private $sftp;

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
     * @return resource
     */
    private function getSftp()
    {
        if (!isset($this->sftp)) {
            throw new RuntimeException('Non connecté (sftp)');
        }
        return $this->sftp;
    }

    /**
     * @return resource
     */
    private function getConnection()
    {
        if (!isset($this->connection)) {
            throw new RuntimeException('Non connecté (connection)');
        }
        return $this->connection;
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function connect(): void
    {
        $this->connection = $this->sftpServiceWrapper->connect($this->host, $this->port);
        $this->sftp = $this->sftpServiceWrapper->login($this->connection, $this->login, $this->password);
    }

    /**
     * @param string $directory
     * @return bool|array
     */
    public function nlist(string $directory): bool|array
    {
        return $this->sftpServiceWrapper->nlist($this->getSftp(), $directory);
    }

    /**
     * @param string $tmp_file
     * @param $remoteFile
     * @return bool
     * @throws \Exception
     */
    public function get(string $tmp_file, $remoteFile): bool
    {
        $this->sftpServiceWrapper->get($this->getSftp(), $tmp_file, $remoteFile);
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
        $this->sftpServiceWrapper->put($this->getSftp(), $remoteFile, $file_to_send);
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
}
