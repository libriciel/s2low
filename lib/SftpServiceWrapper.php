<?php

namespace S2lowLegacy\Lib;

use Exception;

class SftpServiceWrapper
{
    public function connect($host, $port)
    {
        $connection = ssh2_connect($host, $port);
        if (! $connection) {
            throw new Exception("Could not connect to $host on port $port.");
        }

        return $connection;
    }

    public function sslConnect($host, $port, $timeout)
    {
        // TODO: Implement sslConnect() method.
    }

    public function login($ftp, $login, $password)
    {
        if (ssh2_auth_password($ftp, $login, $password)) {
            echo "Authentication Successful!\n";
        } else {
            throw new Exception("Impossible d'authentifier l'utilisateur $login");
        }
        $sftp = ssh2_sftp($ftp);
        if (!$sftp) {
            throw new Exception("Erreur lors de l'authentification");
        }
        return $sftp;
    }

    public function pasv($ftp, bool $pasv)
    {
        // TODO: Implement pasv() method.
    }

    public function chdir($ftp, $remotePath)
    {
        // TODO: Implement chdir() method.
    }

    public function nlist($ftp, $baseFtpDirectory = "./")  // ATTENTION !!!! le $ftp correspond au $sftp
    {
        $sftp_fd = intval($ftp);

// https://stackoverflow.com/questions/8840883/how-to-list-files-of-a-directory-in-an-other-server-using-ssh2
        $path = "ssh2.sftp://$sftp_fd/$baseFtpDirectory";
        $handle = opendir($path);

        $entries = [];
        while (false != ($entry = readdir($handle))) {
            $entries[] = $entry;
        }

        return $entries;
    }

    public function get($ftp, $tmp_file, $remoteFile, $mode = FTP_ASCII)
    {
        $sftp_fd = intval($ftp);

// https://stackoverflow.com/questions/8840883/how-to-list-files-of-a-directory-in-an-other-server-using-ssh2

        $path = "ssh2.sftp://$sftp_fd/$remoteFile";
        $stream = fopen("$path", 'r');
        if (!$stream) {
            throw new Exception("[SFTP] Impossible d'ouvrir le fichier distant : $path");
        }
        $length = filesize($path);
        stream_set_chunk_size($stream, 1024 * 1024);
        $contents = fread($stream, $length);
        file_put_contents($tmp_file, $contents);
        fclose($stream);
    }

    public function delete($ftp, $file)
    {
        // TODO: Implement delete() method.
    }

    public function close($ftp)
    {
        ssh2_disconnect($ftp);
        // TODO: Implement close() method.
    }

    public function raw($ftp, $command)
    {
        // TODO: Implement raw() method.
    }

    public function put($ftp, $remoteFile, $localFile)
    {
        $sftp_fd = intval($ftp);
        // https://stackoverflow.com/questions/8840883/how-to-list-files-of-a-directory-in-an-other-server-using-ssh2
        $path = "ssh2.sftp://$sftp_fd/./";
        $stream = fopen("$path$remoteFile", 'w');
        var_dump("$path$remoteFile");
        if (!$stream) {
            throw new Exception("[SFTP] Impossible d'ouvrir le fichier distant : $path$remoteFile");
        }
        $dataToSend = file_get_contents($localFile);
        if ($dataToSend === false) {
            throw new Exception("[SFTP] Impossible d'ouvrir le fichier local $localFile");
        }
        if (fwrite($stream, $dataToSend) === false) {
            throw new Exception("[SFTP] Impossible d'envoyer les données de $localFile vers $path$remoteFile");
        }
        fclose($stream);
    }
}
