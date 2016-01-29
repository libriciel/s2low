<?php

require_once( __DIR__ . "/../init/init.php");

echo "Début du script\n";

$ocre_list = glob(HELIOS_OCRE_FILE_PATH."/*.ocre");

echo count($ocre_list)." fichiers à envoyer.\n";

if (count($ocre_list) < 1){
	exit;
}

$ssh2 = new SSH2();
$ssh2->setServerName(OCRE_SERVER_DESTINATION,OCRE_SERVER_FINGERRINT,OCRE_SERVER_PORT);
$ssh2->setPasswordAuthentication(OCRE_SERVER_LOGIN,OCRE_SERVER_PASSWORD);
echo "Connexion au serveur SSH\n";


$lock_file = HELIOS_OCRE_FILE_PATH."/.lock";
$distant_lock_file = OCRE_SERVER_DESTINATION_PATH."/.lock";

file_put_contents($lock_file,"");
if (! $ssh2->sendFile($lock_file,$distant_lock_file)){
	echo $ssh2->getLastError()."\n";
	exit;
}
echo "Répertoire distant : pose d'un fichier .lock\n";



foreach($ocre_list as $file_to_send){
	echo "Sending : $file_to_send\n";
	if (! $ssh2->sendFile($file_to_send,OCRE_SERVER_DESTINATION_PATH."/".basename($file_to_send))){
		echo "Impossible d'envoyer le fichier !\n";
	} else {
		unlink($file_to_send);
		echo "Supression du fichier\n";
	}

}

$ssh2->deleteFile($distant_lock_file);
unlink($lock_file);
echo "Répertoire distant : suppression du fichier .lock\n";
