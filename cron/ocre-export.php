<?php

/* Fichier a mettre sur S2low afin d'envoyer le fichier ocre */
require_once(__DIR__ . "/../init/init.php");
LegacyObjectsManager::setLegacyObjectInstancier();


$dir_handle = opendir(HELIOS_OCRE_FILE_PATH);

if (! $dir_handle) {
    echo "Impossible d'ouvrir le répertoire " . HELIOS_OCRE_FILE_PATH;
    exit(-1);
}


echo "Envoi des fichier du répertoire : " . HELIOS_OCRE_FILE_PATH . "\n";
$sigtermHandler = SigTermHandler::getInstance();
while (false !== ($file = readdir($dir_handle))) {
    $file_path = HELIOS_OCRE_FILE_PATH . "/" . $file;
    if (! is_file($file_path)) {
        continue;
    }
    echo "Envoi du fichier $file\n";


    $request = curl_init(HELIOS_OCRE_EXPORT_URL);
    curl_setopt($request, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($request, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($request, CURLOPT_POST, true);
    curl_setopt(
        $request,
        CURLOPT_POSTFIELDS,
        array(
            'passphrase' => HELIOS_OCRE_PASSWORD,
            'ocre' => new CURLFile($file_path)
        )
    );

    curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($request);
    curl_close($request);
    echo "Result : $result\n";
    $decode = json_decode($result, true);
    if (! $decode || empty($decode['result']) || $decode['result'] != 'OK') {
        echo "ECHEC de l'envoi\n";
        continue;
    }

    echo "Envoi OK\n";
    echo "Suppression de $file_path\n";
    unlink($file_path);
    if ($sigtermHandler->isSigtermCalled()) {
        break;
    }
}

sleep(10);
