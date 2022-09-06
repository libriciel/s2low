<?php

// Script permettant de tester l'accÃ¨s au serveur FTP de la DGFiP
// Sortie au format Influxdb
use S2lowLegacy\Class\helios\FTPHeliosSender;

require_once(__DIR__ . "/../../init/init.php");
\S2lowLegacy\Class\LegacyObjectsManager::setLegacyObjectInstancier();


try {
    $ftp = new FTPHeliosSender();
    $ftp->connect(HELIOS_FTP_SERVER, HELIOS_FTP_PORT, HELIOS_FTP_LOGIN, HELIOS_FTP_PASSWORD);
    $ftp->setPassiveMode(HELIOS_FTP_PASSIVE_MODE);
    $ftp->disconnect();
    echo "helios_ftp vpn=1";
} catch (Exception $e) {
    echo "helios_ftp vpn=0";
}
