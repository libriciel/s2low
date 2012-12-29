#! /usr/bin/php
<?php
$start = time();
$min_exec_time = 60;

require_once (__DIR__."/../config/config.php");
require_once (__DIR__."/../class/FTP.class.php");

$ftp = new FTP();
$ftp->setConnexionInfo(HELIOS_FTP_SERVER, HELIOS_FTP_PORT, HELIOS_FTP_LOGIN, HELIOS_FTP_PASSWORD);

try {
	$ftp->recupAll(HELIOS_FTP_RESPONSE_SERVER_PATH, HELIOS_FTP_RESPONSE_TMP_LOCAL_PATH);
	echo "Récupération terminée";
} catch (Exception $e){
	echo "Problème lors de la récupération des enveloppes : " . $e->getMessage() ." \n";
	exit;
}



touch(HELIOS_UPSTART_TOUCH_FILE);


$stop = time();
$sleep = $min_exec_time - ($stop -$start);
if ($sleep > 0){
	echo "Arret du script : $sleep";
	sleep($sleep);
}