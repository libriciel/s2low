<?php

// Script permettant de tester l'accès au serveur FTP de la DGFiP
// Sortie au format Influxdb
use S2low\Services\Helios\DGFiPConnection\FTPConnection;
use S2low\Services\Helios\DGFiPConnection\Protocol;
use S2low\Services\Helios\DGFiPConnection\Protocols\FtpServiceWrapper;
use S2lowLegacy\Class\LegacyObjectsManager;

require_once(__DIR__ . '/../../init/init.php');
LegacyObjectsManager::setLegacyObjectInstancier();


try {
    $ftp = new FTPConnection(
        new Protocol(Protocol::FTP),
        HELIOS_FTP_SERVER,
        HELIOS_FTP_PORT,
        HELIOS_FTP_LOGIN,
        HELIOS_FTP_PASSWORD,
        HELIOS_FTP_PASSIVE_MODE,
        new FtpServiceWrapper()
    );
    $ftp->connect();
    $ftp->close();
    echo "helios_ftp vpn=1";
} catch (Exception $e) {
    echo "helios_ftp vpn=0";
}
