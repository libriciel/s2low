<?php

use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\CloudStorageFactory;
use S2lowLegacy\Class\helios\HeliosAnalyseFichierRecuWorker;
use S2lowLegacy\Class\helios\PESAcquitCloudStorage;
use S2lowLegacy\Class\S2lowLogger;

require_once(__DIR__ . "/../../init/init.php");

list($cloudStorageFactory,$workerScript,$helios_ftp_response_tmp_local_path) = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [CloudStorageFactory::class,\S2lowLegacy\Class\WorkerScript::class,"helios_ftp_response_tmp_local_path"]
    );

$pesAcquitCloudStorage = $cloudStorageFactory->getInstanceByClassName(PESAcquitCloudStorage::class);

$transaction_id = (int) $argv[1];

$path = $pesAcquitCloudStorage->getPath($transaction_id);

if (empty($path)) {
    echo "[$transaction_id] Path vide, ignoré\n";
    return 0;
}
$filename = basename($path);
$destination = $helios_ftp_response_tmp_local_path . "/$filename";
echo "[$transaction_id] Copie de $path vers $destination\n";

copy($path, $destination);
$workerScript->putJobByClassName(HeliosAnalyseFichierRecuWorker::class, $filename);

return 0;
