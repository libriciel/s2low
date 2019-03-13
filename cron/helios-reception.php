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
try {
    /* Reception des fichiers*/
    $ftp->recupAll(HELIOS_FTP_RESPONSE_SERVER_PATH, HELIOS_FTP_RESPONSE_TMP_LOCAL_PATH);
    echo "Recuperation terminee\n";
} catch (Exception $e){
    echo "Probleme lors de la recuperation des enveloppes : " . $e->getMessage() ." \n";
    exit;
}

sleep(60);
