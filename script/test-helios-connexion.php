<?php

require_once(__DIR__ . "/../init/init.php");

$remote_path = "retrait";

$host = HELIOS_FTP_SERVER;
$port = HELIOS_FTP_PORT;
$login = HELIOS_FTP_LOGIN;
$password = HELIOS_FTP_PASSWORD;
$remoteSendPath = HELIOS_SENDING_DESTINATION;
$remoteRetrievePath = HELIOS_FTP_RESPONSE_SERVER_PATH;

$authoritySQL = $objectInstancier->get(AuthoritySQL::class);
$authorityInfo = $authoritySQL->getInfo(2);
$p_dest = $authorityInfo["helios_ftp_dest"];

$file_path = __DIR__."/../test/PHPUnit/helios/fixtures/pes_acquit.xml";

$pesAller = new PesAller();
$p_msg = $pesAller->getP_MSG($file_path);


$ftpService = new FTPService(
    new FtpServiceWrapper(),
    $host,
    $port,
    $login,
    $password
);

if ( !in_array( $argc,[1,2]) || ($argc == 2 && $argv[1] != "testUpload") ){
    echo "Erreur : ".$argv[1]."\n";
    exit(-1);
}

$testUpload=false;

if ($argc == 2 && $argv[1] == "testUpload" ){
    echo "test Upload actif\n";
    $testUpload=true;
}

$ftpService->connect();

// WTF : lancer cette fonction empêche de lancer le sendOneFile apres ...
//var_dump($ftpService->getFileNames("/depot"));

if($testUpload){
    $ftpService->setPassiveMode(HELIOS_FTP_PASSIVE_MODE);
    $command = "site meta P_DEST={$p_dest};P_APPLI=THELPES2;P_MSG=$p_msg";
    echo "$command\n";
    $ftpService->sendRawCommand($command, false/*HELIOS_SENDING_MODE_DEMO*/);
    $ftpService->sendOneFile("depot/"/*HELIOS_SENDING_DESTINATION*/, $file_path);
}

var_dump($ftpService->getFileNames($remoteSendPath));

var_dump($ftpService->getFileNames($remoteRetrievePath));

$ftpService->disconnect();