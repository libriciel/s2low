<?php

use S2lowLegacy\Class\helios\PESAllerCloudStorage;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;
use S2lowLegacy\Lib\SigTermHandler;
use S2lowLegacy\Lib\SQLQuery;

require_once __DIR__ . '/../../init/init.php';
list($objectInstancier, $sqlQuery) = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [ObjectInstancier::class, SQLQuery::class]
    );

// supprime les fichier de plus de nb jours avec nb passé en parametre
// mettre la chaine de caractère ok en second parametre pour faire la suppression
// Attention, une fois qu'on a passé ce script, il ne sert plus à rien, il vaut mieux passer par helios-menage.php qui fonctionne avec beanstalked

$openStackSwiftWrapper = $objectInstancier->get(OpenStackSwiftWrapper::class);

if (isset($argv[1])) {
    $nb_days = $argv[1];
} else {
    $nb_days = 99999;
}
if (intval($nb_days) == 0) {
    $nb_days = 99999;
}

if (empty($argv[2]) || $argv[2] != 'ok') {
    $confirm = false;
} else {
    $confirm = true;
}

echo "Suppression des fichiers de plus de $nb_days jours\n";

$submission_date = date("Y-m-d", strtotime("-$nb_days days"));

$sql = "SELECT id,sha1,filename,submission_date FROM helios_transactions WHERE is_in_cloud=TRUE AND submission_date<? ORDER BY id ASC";

$sqlQuery->prepareAndExecute($sql, $submission_date);

echo "Il y a un certain nombre de PES ALLER à analyser\n";
$sigtermHandler = SigTermHandler::getInstance();
while ($sqlQuery->hasMoreResult()) {
    $pes = $sqlQuery->fetch();
    echo "Analyse du fichier {$pes['sha1']} - {$pes['id']} - {$pes['submission_date']}\n";
    $filename = HELIOS_FILES_UPLOAD_ROOT . "/{$pes['sha1']}";
    if (! file_exists($filename)) {
        echo "Le fichier n'existe pas... [PASS]\n";
        continue;
    }
    if ($openStackSwiftWrapper->fileExistsOnCloud(PESAllerCloudStorage::CONTAINER_NAME, $pes['sha1'])) {
        echo "Le fichier existe sur le cloud, supression...\n";
        if ($confirm) {
            unlink($filename);
            echo "Fichier $filename supprimé [OK] \n";
        } else {
            echo "Le fichier $filename aurait été supprimé [PASS]\n";
        }
    }
    if ($sigtermHandler->isSigtermCalled()) {
        break;
    }
}
