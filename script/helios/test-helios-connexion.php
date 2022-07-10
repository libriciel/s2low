<?php

require_once(__DIR__ . "/../../init/init.php");

$host = HELIOS_FTP_SERVER;
$port = HELIOS_FTP_PORT;
$login = HELIOS_FTP_LOGIN;
$password = HELIOS_FTP_PASSWORD;
$remoteSendPath = HELIOS_SENDING_DESTINATION;
$remoteRetrievePath = HELIOS_FTP_RESPONSE_SERVER_PATH;
$helios_sending_mode_demo = HELIOS_SENDING_MODE_DEMO;
$helios_ftp_passive_mode = HELIOS_FTP_PASSIVE_MODE;
$helios_ftp_passtrans_mode = HELIOS_FTP_PASSTRANS_MODE;

$authoritySQL = $objectInstancier->get(AuthoritySQL::class);
$authorityInfo = $authoritySQL->getInfo(2);
$p_dest = $authorityInfo["helios_ftp_dest"];

$file_path = __DIR__ . "/../test/PHPUnit/helios/fixtures/pes_acquit.xml";

$pesAller = new PesAller();
$p_msg = $pesAller->getP_MSG($file_path);


$ftpService = new FTPService(
    new FtpServiceWrapper(),
    $host,
    $port,
    $login,
    $password,
    $helios_sending_mode_demo,
    $helios_ftp_passive_mode,
    $helios_ftp_passtrans_mode
);

if (!in_array($argc, [1,2]) || ($argc == 2 && $argv[1] != "testUpload")) {
    echo "Erreur : " . $argv[1] . "\n";
    exit(-1);
}

$testUpload = false;

if ($argc == 2 && $argv[1] == "testUpload") {
    echo "test Upload actif\n";
    $testUpload = true;
}

$ftpService->connect();

// WTF : lancer cette fonction empêche de lancer le sendOneFile apres ...
//var_dump($ftpService->getFileNames("/depot"));

if ($testUpload) {
    $ftpService->setPassiveMode(HELIOS_FTP_PASSIVE_MODE);
    $command = "site meta P_DEST={$p_dest};P_APPLI=THELPES2;P_MSG=$p_msg";
    echo "$command\n";
    $ftpService->sendRawCommand($command);
    $ftpService->sendOneFile($remoteSendPath, $file_path);
}

var_dump($ftpService->getFileNames($remoteSendPath));

var_dump($ftpService->getFileNames($remoteRetrievePath));

$ftpService->disconnect();
