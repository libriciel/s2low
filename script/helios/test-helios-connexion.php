<?php

use S2low\Services\Helios\HeliosConnectionBuilder;
use S2lowLegacy\Class\S2lowLogger;
use S2lowLegacy\Lib\FtpServiceWrapper;
use S2lowLegacy\Lib\PesAller;
use S2lowLegacy\Model\AuthoritySQL;

require_once(__DIR__ . "/../../init/init.php");
list($authoritySQL,$s2lowLogger) = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([AuthoritySQL::class,S2lowLogger::class]);

$host = HELIOS_FTP_SERVER;
$port = HELIOS_FTP_PORT;
$login = HELIOS_FTP_LOGIN;
$password = HELIOS_FTP_PASSWORD;
$remoteSendPath = HELIOS_SENDING_DESTINATION;
$remoteRetrievePath = HELIOS_FTP_RESPONSE_SERVER_PATH;
//$helios_sending_mode_demo = HELIOS_SENDING_MODE_DEMO;
$helios_ftp_passive_mode = HELIOS_FTP_PASSIVE_MODE;
$helios_ftp_passtrans_mode = HELIOS_FTP_PASSTRANS_MODE;

$authorityInfo = $authoritySQL->getInfo(2);
$p_dest = $authorityInfo["helios_ftp_dest"];

$file_path = __DIR__ . "/../../test/PHPUnit/helios/fixtures/pes_acquit.xml";

$pesAller = new PesAller();
$p_msg = $pesAller->getP_MSG($file_path);


$heliosConnectionBuilder = new HeliosConnectionBuilder(
    $s2lowLogger,
    new \S2low\Services\Helios\FTPConnection\ActiveConnectionFactory(
        new FtpServiceWrapper(),
        new \S2lowLegacy\Lib\SftpServiceWrapper(),
        $s2lowLogger
    )
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

$configuration = (new \S2low\Services\Helios\FTPConnection\FullConfigurationBuilder())
    ->generateConfiguration(
        $host,
        $port,
        $login,
        $password,
        $helios_ftp_passtrans_mode,
        $helios_ftp_passive_mode,
        $remoteSendPath,
        $remoteRetrievePath
    );

$heliosConnection = $heliosConnectionBuilder->connect($configuration);

// WTF : lancer cette fonction empêche de lancer le sendOneFile apres ...
//var_dump($ftpService->getFileNames("/depot"));

if ($testUpload) {
    /*$heliosConnection->setPassiveMode(HELIOS_FTP_PASSIVE_MODE);
    $command = "site meta P_DEST={$p_dest};P_APPLI=THELPES2;P_MSG=$p_msg";
    echo "$command\n";
    $heliosConnection->sendRawCommand($command);
    $heliosConnection->sendOneFile($remoteSendPath, $file_path);*/
    $heliosConnection->sendOneFileWithProperties($p_dest, $p_msg, "THELPES2", $remoteSendPath, $file_path);
}

var_dump($heliosConnection->getFileNames());

var_dump($heliosConnection->getFileNames());

$heliosConnection->disconnect();
