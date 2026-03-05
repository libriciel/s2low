<?php

namespace S2low\Services\Helios\DGFiPConnection\Protocols;

/**
 * Permet de mocker les retours des fonctions ftp_ pour faciliter les tests unitaires
 */
class FtpServiceWrapper
{
    /**
     * @param string $host
     * @param int $port
     * @param int $timeout
     * @return bool|FtpConnectionWrapper
     */
    public function connect(string $host, int $port, int $timeout): bool|FtpConnectionWrapper
    {
        $connection = ftp_connect($host, $port, $timeout);
        if (!$connection) {
            return false;
        }
        return new FtpConnectionWrapper($connection);
    }

    /**
     * @param string $host
     * @param int $port
     * @param int $timeout
     * @return bool|FtpConnectionWrapper
     */
    public function sslConnect(string $host, int $port, int $timeout): bool|FtpConnectionWrapper
    {
        $connection = ftp_ssl_connect($host, $port, $timeout);
        if (!$connection) {
            return false;
        }
        return new FtpConnectionWrapper($connection);
    }

    /**
     * @param FtpConnectionWrapper $ftp
     * @param string $login
     * @param string $password
     * @return bool
     */
    public function login(FtpConnectionWrapper $ftp, string $login, string $password): bool
    {
        return ftp_login($ftp->getConnection(), $login, $password);
    }

    /**
     * @param FtpConnectionWrapper $ftp
     * @param bool $pasv
     * @return bool
     */
    public function pasv(FtpConnectionWrapper $ftp, bool $pasv): bool
    {
        ftp_set_option($ftp->getConnection(), FTP_USEPASVADDRESS, false);
        return ftp_pasv($ftp->getConnection(), $pasv);
    }

    /**
     * @param FtpConnectionWrapper $ftp
     * @param string $remotePath
     * @return bool
     */
    public function chdir(FtpConnectionWrapper $ftp, string $remotePath): bool
    {
        return ftp_chdir($ftp->getConnection(), $remotePath);
    }

    /**
     * @param FtpConnectionWrapper $ftp
     * @param $baseFtpDirectory
     * @return array|bool
     */
    public function nlist(FtpConnectionWrapper $ftp, string $baseFtpDirectory): array|bool
    {
        return ftp_nlist($ftp->getConnection(), $baseFtpDirectory);
    }

    /**
     * @param FtpConnectionWrapper $ftp
     * @param string $tmp_file
     * @param string $remoteFile
     * @param int $mode
     * @return bool
     */
    public function get(FtpConnectionWrapper $ftp, string $tmp_file, string $remoteFile, $mode = FTP_ASCII): bool
    {
        return ftp_get($ftp->getConnection(), $tmp_file, $remoteFile, $mode);
    }

    /**
     * @param FtpConnectionWrapper $ftp
     * @param $file
     * @return bool
     */
    public function delete(FtpConnectionWrapper $ftp, $file): bool
    {
        return ftp_delete($ftp->getConnection(), $file);
    }

    /**
     * @param FtpConnectionWrapper $ftp
     * @return bool
     */
    public function close(FtpConnectionWrapper $ftp): bool
    {
        return ftp_close($ftp->getConnection());
    }

    /**
     * @param FtpConnectionWrapper $ftp
     * @param string $command
     * @return array|null
     */
    public function raw(FtpConnectionWrapper $ftp, string $command): ?array
    {
        return ftp_raw($ftp->getConnection(), $command);
    }

    /**
     * @param FtpConnectionWrapper $ftp
     * @param string $remoteFile
     * @param string $localFile
     * @param int $mode
     * @return bool
     */
    public function put(FtpConnectionWrapper $ftp, string $remoteFile, string $localFile, $mode = FTP_BINARY): bool
    {
        return ftp_put($ftp->getConnection(), $remoteFile, $localFile, $mode);
    }
    public function pwd(FtpConnectionWrapper $ftp): string
    {
        return ftp_pwd($ftp->getConnection());
    }
}
