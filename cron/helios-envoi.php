#! /usr/bin/php
<?php
declare(ticks = 1);

require_once (__DIR__."/../config/config.php");


require_once (__DIR__."/../class/FTP.class.php");


$ftp = new FTP();
$ftp->setConnexionInfo(HELIOS_FTP_SERVER, HELIOS_FTP_PORT, HELIOS_FTP_LOGIN, HELIOS_FTP_PASSWORD);
if (HELIOS_SENDING_MODE_DEMO){
    $ftp->setDeleteFileAfterDownload();
}

/* Envois des fichiers*/
/** @var HeliosEnvoiControler $heliosEnvoiControler */
$heliosEnvoiControler = $objectInstancier->get("HeliosEnvoiControler");
$heliosEnvoiControler->sendAllTransactions();

sleep(60);
