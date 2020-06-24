<?php

require_once(__DIR__ . "/../init/init.php");

$remote_path = "retrait";

$host = HELIOS_FTP_SERVER;
$port = HELIOS_FTP_PORT;
$login = HELIOS_FTP_LOGIN;
$password = HELIOS_FTP_PASSWORD;

$ftp = ftp_ssl_connect($host,$port);

if (!$ftp){
throw new Exception("Impossible de se connecter au serveur {$host}:{$port}");
}

try{
if ($login){
$ftp_login = ftp_login($ftp, $login, $password);
var_dump($ftp_login);
if (!$ftp_login){
 echo "Impossible de se connecter avec le login {$login}:{$password}";
}
}
} catch (Exception $e){
echo $e->getMessage();
die();
}

ftp_close($ftp);