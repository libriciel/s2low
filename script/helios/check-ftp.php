<?php
// Script permettant de tester l'accès au serveur FTP de la DGFiP
// Sortie au format Influxdb
require_once( __DIR__."/../../init/init.php");


try {
    $ftp = new FTPHeliosSender();
    $ftp->connect(HELIOS_FTP_SERVER, HELIOS_FTP_PORT, HELIOS_FTP_LOGIN,HELIOS_FTP_PASSWORD);
    $ftp->setPassiveMode(HELIOS_FTP_PASSIVE_MODE);
    $ftp->disconnect();
    echo "helios_ftp vpn=1";
} catch (Exception $e){
    echo "helios_ftp vpn=0";
}
