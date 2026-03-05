<?php

namespace S2low\Services\Helios\DGFiPConnection;

use Exception;
use RuntimeException;
use S2low\Services\Helios\DGFiPConnection\Protocols\FtpConnectionWrapper;
use S2low\Services\Helios\DGFiPConnection\Protocols\FtpServiceWrapper;

/**
 * Contient une connection vers un serveur FTP ou FTPS
 *
 */
class FTPConnection
{
    public const TIMEOUT = 90;
    /**
     * @var FtpServiceWrapper
     */
    private FtpServiceWrapper $ftpServiceWrapper;
    private FtpConnectionWrapper $ftp;
    /**
     * @var Protocol
     */
    private Protocol $protocol;
    private string $host;
    private string $port;
    private string $login;
    private string $password;
    private bool $isPassiveMode;

    /**
     * @param Protocol $protocol
     * @param string $host
     * @param string $port
     * @param string $login
     * @param string $password
     * @param bool $isPassiveMode
     * @param FtpServiceWrapper $ftpServiceWrapper
     */
    public function __construct(
        Protocol $protocol,
        string $host,
        string $port,
        string $login,
        string $password,
        bool $isPassiveMode,
        FtpServiceWrapper $ftpServiceWrapper
    ) {
        $this->protocol = $protocol;
        $this->host = $host;
        $this->port = $port;
        $this->login = $login;
        $this->password = $password;
        $this->isPassiveMode = $isPassiveMode;
        $this->ftpServiceWrapper = $ftpServiceWrapper;
    }

    /**
     * @return FtpConnectionWrapper
     */
    private function getFtp(): FtpConnectionWrapper
    {
        if (!$this->isConnected()) {
            throw new RuntimeException('Non connecté');
        }
        return $this->ftp;
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function connect(): void
    {
        if ($this->protocol->isFTPS()) {
            $ftp = $this->ftpServiceWrapper->sslConnect($this->host, $this->port, self::TIMEOUT);
        } else {
            $ftp = $this->ftpServiceWrapper->connect($this->host, $this->port, self::TIMEOUT);
        }

        if (!$ftp) {
            throw new Exception("Impossible de se connecter au serveur $this->host:$this->port");
        }
        $this->ftp = $ftp;
        if ($this->login) {
            $ftp_login = $this->ftpServiceWrapper->login($this->ftp, $this->login, $this->password);
            if (!$ftp_login) {
                throw new Exception("Impossible de se connecter avec le login $this->login");
            }
        }
        $this->ftpServiceWrapper->pasv($this->ftp, $this->isPassiveMode);
    }

    /**
     * @param string $remote_path
     * @return bool
     */
    public function chdir(string $remote_path): bool
    {
        return $this->ftpServiceWrapper->chdir($this->getFtp(), $remote_path);
    }

    /**
     * @param string $directory
     * @return array|bool
     */
    public function nlist(string $directory): array|bool
    {
        return $this->ftpServiceWrapper->nlist($this->getFtp(), $directory);
    }

    /**
     * @param string $tmp_file
     * @param string $remoteFile
     * @return bool
     */
    public function get(string $tmp_file, string $remoteFile): bool
    {
        return $this->ftpServiceWrapper->get($this->getFtp(), $tmp_file, $remoteFile);
    }

    /**
     * @param $command
     * @return array|null
     */
    public function raw($command): ?array
    {
        return $this->ftpServiceWrapper->raw($this->getFtp(), $command);
    }

    /**
     * @param string $remoteFile
     * @param string $file_to_send
     * @return bool
     */
    public function put(string $remoteFile, string $file_to_send): bool
    {
        return $this->ftpServiceWrapper->put($this->getFtp(), $remoteFile, $file_to_send);
    }

    /**
     * @param $file
     * @return bool
     */
    public function delete($file): bool
    {
        return $this->ftpServiceWrapper->delete($this->getFtp(), $file);
    }

    /**
     * @return bool
     */
    public function close(): bool
    {
        return $this->ftpServiceWrapper->close($this->getFtp());
    }

    /**
     * @return string
     */
    public function getPassiveAsString(): string
    {
        return $this->isPassiveMode ? 'Passif' : 'Actif';
    }

    /**
     * @return string
     */
    public function getURL(): string
    {
        return "$this->protocol://$this->login:$this->password@$this->host [{$this->getPassiveAsString()}]";
    }

    /**
     * @return bool
     */
    public function isConnected(): bool
    {
        return isset($this->ftp);
    }

    public function putwd(): string
    {
        return $this->ftpServiceWrapper->pwd($this->getFtp());
    }
}
