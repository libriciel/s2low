<?php

namespace S2low\Services\Helios\DGFiPConnection\Protocols;

use Exception;
use phpseclib3\Net\SFTP;
use S2low\Services\Helios\DGFiPConnection\FTPFileRetrieveException;

/**
 * Permet de mocker les retours des fonctions ssh2 et autres utilisées pour la connection sftp
 * pour faciliter les tests unitaires
 */
class SftpServiceWrapper
{
    /**
     * @param $host
     * @param $port
     * @return \phpseclib3\Net\SFTP
     * @throws Exception
     */
    public function connect($host, $port): SFTP // TODO : handle timeout !!
    {
        return new SFTP($host, $port);
    }

    /**
     * @param \phpseclib3\Net\SFTP $sftp
     * @param string $login
     * @param string $password
     * @return void
     * @throws \Exception
     */
    public function login(SFTP $sftp, string $login, string $password): void
    {
        if (!$sftp->login($login, $password)) {
            throw new Exception('Impossible de se connecter au serveur SFTP');   //TODO : trouver l'exception adéquate
        }
    }

    /**
     * @param \phpseclib3\Net\SFTP $SFTP
     * @param string $baseFtpDirectory
     * @return bool|array
     * @throws \Exception
     */
    public function nlist(SFTP $SFTP, string $baseFtpDirectory = './'): bool|array
    {
        $nlist = $SFTP->nlist($baseFtpDirectory);
        if (!$nlist) {
            throw new Exception("[nlist] Impossible d'obtenir le contenu de $baseFtpDirectory");
        }
        return array_diff($nlist, ['.', '..']);
    }

    /**
     * @param \phpseclib3\Net\SFTP $ftp
     * @param $tmp_file
     * @param $remoteFile
     * @param int $mode
     * @return void
     * @throws \Exception
     * @throws \S2low\Services\Helios\DGFiPConnection\FTPFileRetrieveException
     */
    public function get(SFTP $ftp, $tmp_file, $remoteFile, int $mode = FTP_ASCII): void
    {
        $result = $ftp->get($remoteFile, $tmp_file);
        if (!$result) {
            throw new FTPFileRetrieveException("[SFTP] Impossible d'ouvrir le fichier distant : $remoteFile");
        }
    }

    /**
     * @param \phpseclib3\Net\SFTP $ftp
     * @param $remoteFile
     * @param $localFile
     * @return void
     * @throws \Exception
     */
    public function put(SFTP $ftp, $remoteFile, $localFile): void
    {
        if (!$ftp->put($remoteFile, $localFile, SFTP::SOURCE_LOCAL_FILE)) {
            var_dump($ftp->getErrors());
            throw new Exception("Unable to put $localFile on $remoteFile");
        }
    }

    /**
     * @param \phpseclib3\Net\SFTP $ftp
     * @return void
     */
    public function close(SFTP $ftp): void
    {
        $ftp->disconnect();
    }

    /**
     * @param \phpseclib3\Net\SFTP $ftp
     * @param string $remote_path
     * @return bool
     */
    public function chdir(SFTP $ftp, string $remote_path): bool
    {
        return $ftp->chdir($remote_path);
    }
    public function pwd(SFTP $ftp): string
    {
        return $ftp->pwd();
    }
}
