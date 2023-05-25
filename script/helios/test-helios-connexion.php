<?php

use S2low\Services\Helios\DGFiPConnection\DGFiPConnectionBuilder;
use S2low\Services\Helios\DGFiPConnection\Protocols\FtpServiceWrapper;
use S2low\Services\Helios\DGFiPConnection\Protocols\SftpServiceWrapper;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\S2lowLogger;
use S2lowLegacy\Lib\PesAller;
use S2lowLegacy\Model\AuthoritySQL;

require_once(__DIR__ . "/../../init/init.php");
list($authoritySQL,$s2lowLogger) = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([AuthoritySQL::class,S2lowLogger::class]);

$host = HELIOS_FTP_SERVER;
$port = HELIOS_FTP_PORT;
$login = HELIOS_FTP_LOGIN;
$password = HELIOS_FTP_PASSWORD;
$remoteSendPath = HELIOS_SENDING_DESTINATION;
$remoteRetrievePath = HELIOS_FTP_RESPONSE_SERVER_PATH;
$helios_ftp_passive_mode = HELIOS_FTP_PASSIVE_MODE;
$helios_ftp_passtrans_mode = HELIOS_FTP_CONNECTION_MODE;

$authorityInfo = $authoritySQL->getInfo(2);
$p_dest = $authorityInfo["helios_ftp_dest"];

$file_path = __DIR__ . "/../../test/PHPUnit/helios/fixtures/pes_acquit.xml";

$pesAller = new PesAller();
$p_msg = $pesAller->getP_MSG($file_path);


if (!in_array($argc, [1,2]) || ($argc == 2 && $argv[1] != "testUpload")) {
    echo "Erreur : " . $argv[1] . "\n";
    exit(-1);
}

$testUpload = false;

if ($argc == 2 && $argv[1] == "testUpload") {
    echo "test Upload actif\n";
    $testUpload = true;
}

$DGFiPConnection = (new DGFiPConnectionBuilder(
    new FtpServiceWrapper(),
    new SftpServiceWrapper(),
    $s2lowLogger
))
    ->get(
        $host,
        $port,
        $login,
        $password,
        $helios_ftp_passtrans_mode,
        $helios_ftp_passive_mode,
        $remoteSendPath,
        $remoteRetrievePath,
        "THELPES2"
    );

$DGFiPConnection->connect();

// WTF : lancer cette fonction empêche de lancer le sendOneFile apres ...
//var_dump($ftpService->getFileNames("/depot"));

if ($testUpload) {
    $DGFiPConnection->sendOneFileWithProperties($p_dest, $p_msg, "THELPES2");
}

var_dump($DGFiPConnection->getFileNames());

var_dump($DGFiPConnection->getFileNames());

$DGFiPConnection->disconnect();
