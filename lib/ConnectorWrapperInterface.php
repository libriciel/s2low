<?php

namespace S2lowLegacy\Lib;

interface ConnectorWrapperInterface
{
    public function connect($host, $port, $timeout);

    public function sslConnect($host, $port, $timeout);

    public function login($ftp, $login, $password);

    public function pasv($ftp, bool $pasv);

    public function chdir($ftp, $remotePath);

    public function nlist($ftp, $baseFtpDirectory);

    public function get($ftp, $tmp_file, $remoteFile, $mode = FTP_ASCII);

    public function delete($ftp, $file);

    public function close($ftp);

    public function raw($ftp, $command);

    public function put($ftp, $remoteFile, $localFile, $mode = FTP_BINARY);
}
