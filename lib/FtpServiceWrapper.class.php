<?php

class FtpServiceWrapper{
    public function sslConnect($host,$port,$timeout){
        return ftp_ssl_connect($host, $port,$timeout);
    }

    public function login($ftp,$login,$password){
        return ftp_login($ftp, $login, $password);
    }

    public function pasv($ftp, bool $pasv){
        return ftp_pasv($ftp, $pasv);
    }

    public function chdir($ftp, $remotePath){
        return ftp_chdir($ftp, $remotePath);
    }

    public function nlist($ftp, $baseFtpDirectory){
        return ftp_nlist($ftp, $baseFtpDirectory);
    }

    public function get($ftp,$tmp_file,$remoteFile,$mode=FTP_ASCII){
        return ftp_get($ftp, $tmp_file, $remoteFile, $mode);
    }

    public function delete($ftp,$file){
        return ftp_delete($ftp, $file);
    }

    public function close($ftp){
        return ftp_close($ftp);
    }

    public function raw($ftp, $command){
        return ftp_raw($ftp, $command);
    }

    public function put($ftp,$remoteFile,$localFile,$mode = FTP_BINARY){
        return ftp_put($ftp,$remoteFile,$localFile,$mode);
    }
}