<?php

/* Fichier a mettre sur S2low afin d'envoyer le fichier ocre */

define("FILES_TO_SEND_DIRECTORY","/Users/eric/ocre/send/");
define("RECEIVE_SCRIPT_URL",'http://localhost/phpstorm/pastell-ocre/script/receive-ocre.php');
define("PASSPHRASE","change_me");

$dir_handle = opendir(FILES_TO_SEND_DIRECTORY);


echo "Envoi des fichier du répertoire : ".FILES_TO_SEND_DIRECTORY."\n";

while (false !== ($file = readdir($dir_handle)) ) {
	$file_path = FILES_TO_SEND_DIRECTORY . "/".$file;
	if (! is_file($file_path)){
		continue;
	}
	echo "Envoi du fichier $file\n";


	$request = curl_init(RECEIVE_SCRIPT_URL);
	curl_setopt($request, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($request, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($request, CURLOPT_POST, true);
	curl_setopt(
		$request,
		CURLOPT_POSTFIELDS,
		array(
			'passphrase' => PASSPHRASE,
			'ocre' => new CURLFile($file_path)
		));

	curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
	$result = curl_exec($request);
	curl_close($request);
	echo "Result : $result\n";
	$decode = json_decode($result,true);
	if (! $decode || empty($decode['result']) || $decode['result'] != 'OK'){
		echo "ECHEC de l'envoi\n";
		continue;
	}

	echo "Envoi OK\n";
	echo "Suppression de $file_path\n";
	unlink($file_path);
}