<?php

require_once(__DIR__ . "/../init/init.php");

$remote_path = "retrait";

$host = HELIOS_FTP_SERVER;
$port = HELIOS_FTP_PORT;
$login = HELIOS_FTP_LOGIN;
$password = HELIOS_FTP_PASSWORD;

$authoritySQL = $objectInstancier->get(AuthoritySQL::class);
$authorityInfo = $authoritySQL->getInfo(2);
$p_dest = $authorityInfo["helios_ftp_dest"];

$file_path = __DIR__."/../test/PHPUnit/helios/fixtures/pes_acquit.xml";

$pesAller = new PesAller();
$p_msg = $pesAller->getP_MSG($file_path);
var_dump($authorityInfo);


$ftpService = new FTPService(
    new FtpServiceWrapper(),
    $host,
    $port,
    $login,
    $password
);

$ftpService->connect();

// WTF : lancer cette fonction empêche de lancer le sendOneFile apres ...
//var_dump($ftpService->getFileNames("/depot"));

$ftpService->setPassiveMode(HELIOS_FTP_PASSIVE_MODE);
$command = "site meta P_DEST={$p_dest};P_APPLI=THELPES2;P_MSG=$p_msg";
echo "$command\n";
$ftpService->sendRawCommand($command, false/*HELIOS_SENDING_MODE_DEMO*/);
$ftpService->sendOneFile("depot/"/*HELIOS_SENDING_DESTINATION*/, $file_path);

var_dump($ftpService->getFileNames("/depot"));

var_dump($ftpService->getFileNames("/retrait"));

$ftpService->disconnect();